<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\Attribute;
use App\Models\Value;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EntityValueController extends Controller
{
    public function create()
    {
        // نحدد الدور الحالي
        $role = Auth::user()->role ?? 'hr';

        // نحدد الاتصال المناسب
        $connection = $role === 'hr' ? 'mysql_hr' : 'mysql_support';

        // نجيب الـ attributes من نفس قاعدة البيانات
        $attributes = Attribute::on($connection)->get();

        return view('entity_values.create', compact('attributes', 'role'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'attributes' => 'required|array|min:1',
            'attributes.*.id' => 'required|integer',
            'attributes.*.value' => 'required|string|max:255',
        ]);

        // تحديد الـ role والـ connection
        $role = Auth::user()->role ?? 'hr';
        $connection = $role === 'hr' ? 'mysql_hr' : 'mysql_support';

        DB::connection($connection)->transaction(function () use ($request, $role, $connection) {
            // إنشاء الـ Entity الجديدة
            $entity = Entity::on($connection)->create([
                'namespace' => 'App\\Entities\\' . ucfirst($role),
                'entity_type' => $role . '_record',
            ]);

            // إنشاء القيم المرتبطة بالـ Attributes
            foreach ($request->input('attributes') as $attr) {
                Value::on($connection)->create([
                    'entity_id' => $entity->id,
                    'attribute_id' => $attr['id'],
                    'value' => $attr['value'],
                ]);
            }
        });

        return redirect()->back()->with('success', 'Values created successfully for ' . strtoupper($role) . '!');
    }
}
