<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use OneLogin\Saml2\Auth as OneLoginAuth;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SamlController extends Controller
{
    /**
     * الحصول على كائن SAML Auth
     */
    protected function samlAuth()
    {
        $settings = config('saml'); // مصفوفة الإعدادات من config/saml.php
        return new OneLoginAuth($settings); // تمرير array مباشرة
    }

    /**
     * إعادة التوجيه إلى IdP (SP-initiated)
     */
    public function login(Request $request)
    {
        $auth = $this->samlAuth();
        // احفظ Request ID للـ InResponseTo validation لاحقاً
        $requestId = '_' . Str::random(24);
        session(['saml.request_id' => $requestId]);

        // redirect إلى IdP
        $auth->login(null, [], false, false, true);

        // fallback إذا login() لم يخرج
        return redirect()->to($auth->getSsoUrl());
    }

    /**
     * ACS: IdP POSTs SAMLResponse
     */
    public function acs(Request $request)
    {
        $auth = $this->samlAuth();

        try {
            $auth->processResponse();
        } catch (\Exception $e) {
            Log::error('SAML processResponse error: '.$e->getMessage());
            return response('SAML processing error', 400);
        }

        $errors = $auth->getErrors();
        if (!empty($errors)) {
            Log::error('SAML errors: '.implode(', ', $errors));
            return response('SAML Error: '.implode(', ', $errors), 400);
        }

        if (!$auth->isAuthenticated()) {
            return response('Not authenticated by IdP', 400);
        }

        // الحصول على الـ raw XML ل parsing يدوي
        $rawXml = $auth->getLastResponseXML();
        if (!$rawXml) {
            $attributes = $auth->getAttributes();
            $nameId = $auth->getNameId();
        } else {
            // منع XXE
            if (function_exists('libxml_disable_entity_loader')) {
                libxml_disable_entity_loader(true);
            }
            libxml_use_internal_errors(true);

            $dom = new \DOMDocument();
            $loaded = $dom->loadXML($rawXml, LIBXML_NONET | LIBXML_NOCDATA | LIBXML_NOBLANKS);
            if (!$loaded) {
                $errs = libxml_get_errors();
                libxml_clear_errors();
                Log::error('SAML XML parse error', ['errors' => $errs]);
                return response('Invalid SAML XML', 400);
            }

            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
            $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');

            $nameIdNode = $xpath->query('//saml:Subject/saml:NameID')->item(0);
            $nameId = $nameIdNode ? trim($nameIdNode->textContent) : null;

            // استخراج Attributes
            $attributes = [];
            $attrNodes = $xpath->query('//saml:AttributeStatement/saml:Attribute');
            foreach ($attrNodes as $attr) {
                $name = $attr->getAttribute('Name');
                $vals = [];
                $valNodes = $xpath->query('.//saml:AttributeValue', $attr);
                foreach ($valNodes as $v) $vals[] = $v->textContent;
                $attributes[$name] = count($vals) === 1 ? $vals[0] : $vals;
            }

            // Conditions check
            $conds = $xpath->query('//saml:Conditions')->item(0);
            if ($conds) {
                $now = new \DateTime('now', new \DateTimeZone('UTC'));
                $nb = $conds->getAttribute('NotBefore');
                $nooa = $conds->getAttribute('NotOnOrAfter');
                if ($nb && $now < new \DateTime($nb)) return response('Assertion not yet valid', 400);
                if ($nooa && $now >= new \DateTime($nooa)) return response('Assertion expired', 400);
            }

            // Audience validation
            $aud = $xpath->query('//saml:Audience')->item(0);
            if ($aud) {
                $spEntityId = config('saml.sp.entityId');
                if (trim($aud->textContent) !== $spEntityId) return response('Invalid audience', 400);
            }

            // InResponseTo validation
            $respNode = $xpath->query('//samlp:Response')->item(0);
            if ($respNode && $respNode->hasAttribute('InResponseTo')) {
                $inResponseTo = $respNode->getAttribute('InResponseTo');
                $saved = session('saml.request_id');
                if ($saved && $inResponseTo !== $saved) {
                    return response('InResponseTo mismatch', 400);
                }
            }
        }

        // اختيار email
        $email = $attributes['email'] ?? $attributes['mail'] ?? $attributes['Email'] ?? $nameId;
        if (!$email) return response('No email supplied', 400);

        // العثور على المستخدم أو إنشاؤه
        $user = User::where('email', $email)->first();
        if (!$user) {
            $user = User::create([
                'name' => $attributes['displayName'] ?? $attributes['name'] ?? explode('@', $email)[0],
                'email' => $email,
                'password' => bcrypt(Str::random(40)),
            ]);
        }

        // تسجيل الدخول
        auth()->login($user);

        // OTP
        if ($user->otp_enabled) {
            $otp = rand(100000, 999999);
            $user->otp_code = $otp;
            $user->otp_expires_at = Carbon::now()->addMinutes(5);
            $user->save();

            session(['otp_user_id' => $user->id, 'otp_pending' => true]);
            auth()->logout();
            return redirect()->route('otp.form');
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * عرض SP metadata
     */
    public function metadata()
    {
        $settings = config('saml');
        $auth = new OneLoginAuth($settings);
        $metadata = $auth->getSettings()->getSPMetadata();
        $errors = $auth->getSettings()->validateMetadata($metadata);

        if (!empty($errors)) {
            return response('Invalid SP metadata: '.implode(', ', $errors), 500);
        }
        return response($metadata, 200)->header('Content-Type', 'application/xml');
    }
}
