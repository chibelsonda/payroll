<?php

namespace App\Services;

/**
 * Tax Calculation Service
 *
 * Implements Philippine BIR (Bureau of Internal Revenue) withholding tax calculation
 * Based on the TRAIN Law (Tax Reform for Acceleration and Inclusion) tax table
 */
class TaxCalculationService
{
    /**
     * Calculate withholding tax based on gross pay
     *
     * Uses TRAIN Law tax brackets (simplified implementation)
     *
     * @param float $grossPay Monthly gross pay
     * @return float Withholding tax amount
     */
    public function calculateWithholdingTax(float $grossPay): float
    {
        // Annual gross pay
        $annualGrossPay = $grossPay * 12;

        // TRAIN Law Tax Brackets (2023 rates)
        // Bracket format: [min, max, base_tax, rate]
        $taxBrackets = [
            [0, 250000, 0, 0],              // 0% for 0-250k
            [250000, 400000, 0, 0.20],      // 20% for 250k-400k (excess over 250k)
            [400000, 800000, 30000, 0.25],  // 25% for 400k-800k (30k + 25% of excess over 400k)
            [800000, 2000000, 130000, 0.30], // 30% for 800k-2M (130k + 30% of excess over 800k)
            [2000000, 8000000, 490000, 0.32], // 32% for 2M-8M (490k + 32% of excess over 2M)
            [8000000, PHP_INT_MAX, 2410000, 0.35], // 35% for above 8M (2.41M + 35% of excess over 8M)
        ];

        $annualTax = 0;

        foreach ($taxBrackets as $bracket) {
            [$min, $max, $baseTax, $rate] = $bracket;

            if ($annualGrossPay > $min) {
                $taxableAmount = min($annualGrossPay, $max) - $min;
                $annualTax = $baseTax + ($taxableAmount * $rate);

                if ($annualGrossPay <= $max) {
                    break;
                }
            }
        }

        // Return monthly tax (divide by 12)
        return round($annualTax / 12, 2);
    }

    /**
     * Calculate withholding tax with personal exemptions
     *
     * @param float $grossPay Monthly gross pay
     * @param int $numberOfDependents Number of dependents (0-4)
     * @return float Withholding tax amount
     */
    public function calculateWithholdingTaxWithExemptions(float $grossPay, int $numberOfDependents = 0): float
    {
        // Personal exemption (no longer applicable under TRAIN Law, but kept for flexibility)
        // Under TRAIN Law, personal exemptions are removed, but this method is kept for future use

        // For now, just use basic calculation
        return $this->calculateWithholdingTax($grossPay);
    }
}
