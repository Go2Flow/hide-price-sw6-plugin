<?php declare(strict_types=1);

namespace Go2FlowHidePrices\Struct;

use Shopware\Core\Framework\Struct\Struct;

class HidePriceStruct extends Struct
{
    private bool $hide = true;

    public function isHide(): bool
    {
        return $this->hide;
    }

    public function setHide(bool $hide): void
    {
        $this->hide = $hide;
    }
}