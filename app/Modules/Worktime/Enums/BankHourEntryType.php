<?php

namespace App\Modules\Worktime\Enums;

enum BankHourEntryType: string
{
    case OpeningBalance = 'opening_balance';
    case ApurationCredit = 'apuration_credit';
    case ApurationDebit = 'apuration_debit';
    case ManualCredit = 'manual_credit';
    case ManualDebit = 'manual_debit';
    case Compensation = 'compensation';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Saldo inicial',
            self::ApurationCredit => 'Crédito de apuração',
            self::ApurationDebit => 'Débito de apuração',
            self::ManualCredit => 'Crédito manual',
            self::ManualDebit => 'Débito manual',
            self::Compensation => 'Compensação',
            self::Adjustment => 'Ajuste',
        };
    }
}
