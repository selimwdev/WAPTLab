<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Attribute;
use App\Models\Value;
use App\Models\Entity;

class AttributeController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    // 🔄 تحديد قاعدة البيانات حسب الـ role
    private function getConnection()
    {
        $role = Auth::user()->role ?? 'main';

        return match ($role) {
            'support' => 'mysql_support',
            'hr' => 'mysql_hr',
            default => 'mysql', // crm_main
        };
    }

    // ✅ عرض كل Attributes
    public function index()
    {
        $connection = $this->getConnection();

        $attributes = Attribute::on($connection)->get();

        return view('attributes.index', compact('attributes'));
    }

    // ✅ إضافة Attribute جديد
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'data_type' => 'required|string'
        ]);

        $connection = $this->getConnection();

        // هنا المشكلة كانت: create() لازم تشتغل على static model مش instance
        Attribute::on($connection)->create([
            'name' => $request->name,
            'data_type' => $request->data_type
        ]);

        return redirect()->back()->with('success', 'Attribute added successfully!');
    }

    // ✅ عرض Values حسب Attribute
    public function values($attribute_id)
    {
        $connection = $this->getConnection();

        $attribute = Attribute::on($connection)->findOrFail($attribute_id);
        $values = Value::on($connection)
            ->where('attribute_id', $attribute_id)
            ->with('entity')
            ->get();

        $entities = Entity::on($connection)->get();

        return view('attributes.values', compact('attribute', 'values', 'entities'));
    }

    // ✅ إضافة Value جديد مربوط بـ Attribute و Entity
    public function storeValue(Request $request, $attribute_id)
    {
        $request->validate([
            'entity_id' => 'required|integer',
            'value' => 'required|string'
        ]);

        $connection = $this->getConnection();

        Value::on($connection)->create([
            'attribute_id' => $attribute_id,
            'entity_id' => $request->entity_id,
            'value' => $request->value
        ]);

        return redirect()->back()->with('success', 'Value added successfully!');
    }
}
