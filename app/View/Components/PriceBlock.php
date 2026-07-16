<?php

namespace App\View\Components;

use App\Support\MoneyFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PriceBlock extends Component
{
    public readonly string $formattedAmount;

    public function __construct(
        string|int $amount,
        public readonly ?string $label = null,
        public readonly ?string $suffix = null,
    ) {
        $this->formattedAmount = MoneyFormatter::format($amount);
    }

    public function render(): View
    {
        return view('components.price-block');
    }
}
