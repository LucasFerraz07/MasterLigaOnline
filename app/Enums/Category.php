<?php

namespace App\Enums;

enum Category: string
{
    case White = 'white';
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';
    case Black = 'black';

    /**
     * Piso de overall padrão de cada categoria, usado ao criar uma nova liga.
     * Cada liga pode sobrescrever esses valores em `league_category_prices.min_overall`.
     */
    public function defaultMinOverall(): int
    {
        return match ($this) {
            self::White => 0,
            self::Bronze => 70,
            self::Silver => 80,
            self::Gold => 90,
            self::Black => 98,
        };
    }

    /**
     * Salário base padrão de cada categoria, usado ao criar uma nova liga.
     * Cada liga pode sobrescrever esses valores em `league_category_prices.base_salary`.
     */
    public function defaultBaseSalary(): int
    {
        return match ($this) {
            self::White => 0,
            self::Bronze => 5000,
            self::Silver => 10000,
            self::Gold => 25000,
            self::Black => 50000,
        };
    }
}
