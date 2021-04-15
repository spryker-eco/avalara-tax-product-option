<?php

/**
 * MIT License
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerEco\Zed\AvalaraTaxProductOption\Business\Mapper;

use Generated\Shared\Transfer\AvalaraLineItemTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\ProductOptionTransfer;

interface AvalaraLineItemMapperInterface
{
    /**
     * @param \Generated\Shared\Transfer\ProductOptionTransfer $productOptionTransfer
     * @param \Generated\Shared\Transfer\AvalaraLineItemTransfer $avalaraLineItemTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     * @param string $priceMode
     *
     * @return \Generated\Shared\Transfer\AvalaraLineItemTransfer
     */
    public function mapProductOptionTransferToAvalaraLineItemTransfer(
        ProductOptionTransfer $productOptionTransfer,
        AvalaraLineItemTransfer $avalaraLineItemTransfer,
        ItemTransfer $itemTransfer,
        string $priceMode
    ): AvalaraLineItemTransfer;
}
