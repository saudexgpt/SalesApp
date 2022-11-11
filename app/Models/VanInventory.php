<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VanInventory extends Model
{
    use HasFactory;
    public function staff()
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
    public function mainInventory()
    {
        return $this->belongsTo(SubInventory::class, 'sub_inventory_id', 'id');
    }

    public function deductFromVanInventory($item_id, $quantity, $rep_id)
    {
        $van_inventories = VanInventory::where(['staff_id' => $rep_id, 'item_id' => $item_id])->where('balance', '>', 0)->get();
        $to_supply = $quantity;
        foreach ($van_inventories as $van_inventory) {
            $stock_balance = $van_inventory->balance;
            if ($to_supply <= $stock_balance) {
                $van_inventory->sold += $to_supply;
                $van_inventory->balance -= $to_supply;
                $van_inventory->save();
                $to_supply = 0;
                break;
            } else {
                $van_inventory->sold += $stock_balance;
                $van_inventory->balance = 0;
                $van_inventory->save();
                $to_supply -= $stock_balance;
            }
        }
    }
    public function addToVanInventory($item_id, $quantity, $rep_id)
    {
        $van_inventories = VanInventory::where(['staff_id' => $rep_id, 'item_id' => $item_id])->where('sold', '>', 0)->get();
        $to_stock = $quantity;
        foreach ($van_inventories as $van_inventory) {
            if ($to_stock > 0) {

                $sold = $van_inventory->sold;
                if ($to_stock <= $sold) {
                    $van_inventory->sold -= $to_stock;
                    $van_inventory->balance += $to_stock;
                    $van_inventory->save();
                    $to_stock = 0;
                    break;
                } else {
                    $van_inventory->sold = 0;
                    $van_inventory->balance += $sold;
                    $van_inventory->save();
                    $to_stock -= $sold;
                }
            }
        }
    }
}
