<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = "transaction";
    protected $primaryKey = "idtransaction";
    public $timestamps = false;
    protected $fillable = [
        'numreservation',
        'montant',
        'date_transaction',
        'moyen_paiement',
        'statut_paiement',
    ];
    protected static function booted()
    {
        static::creating(function ($transaction) {
            if (empty($transaction->idtransaction)) {
                $transaction->idtransaction = Transaction::max('idtransaction') + 1;
            }
        });
    }
}
