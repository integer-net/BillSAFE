<?php

include_once("Mage/Sales/Model/Order/Invoice.php");

class AwHh_Billsafe_Model_Invoicepdf extends Mage_Sales_Model_Order_Pdf_Invoice {

    private $_height = 790;

    const PAGE_POSITION_LEFT = 90;
    const PAGE_POSITION_RIGHT = 555;
    const PAGE_POSITION_TOP = 790;
    const PAGE_POSITION_BOTTOM = 90;
    const PAGE_Y_AFTER_LOGO = 650;

    /**
     * @const FOOTER_SPACING Vertical padding between footer blocks.
     */
    const FOOTER_SPACING = 30;
    const MAX_LOGO_WIDTH = 500;
    const MAX_LOGO_HEIGHT = 50;

    private $current_invoice = NULL;
    private $current_invoice_store = NULL;
    private $current_item_x = 1;

    public function __construct() {
        $this->_pdf = new Zend_Pdf();
    }

    /**
     * make a new line with given font, size and spaceing
     *
     * @param Zend_Pdf_Font $font        font for new line
     * @param float         $fontSize    size for new line
     * @param boolean       $invert      invert the new line (if true it will be upwards)
     * @param float         $spacingSize spacing of the font
     *
     * @return void
     */
    private function _newLine($font, $fontSize, $invert = false, $spacingSize = 1.2) {
        // function comes from MarketReadyGermany

        if ($invert) {
            $this->_height += $this->heightForFontUsingFontSize($font, $fontSize) * $spacingSize;
        } else {
            $this->_height -= $this->heightForFontUsingFontSize($font, $fontSize) * $spacingSize;
        }
    }

    /**
     * Returns the total height in points of the font using the specified font and
     * size.
     *
     * @param Zend_Pdf_Resource_Font $font     Font to calculate height
     * @param float                  $fontSize Font size in points
     *
     * @return float
     */
    public function heightForFontUsingFontSize($font, $fontSize) {
        // function comes from MarketReadyGermany

        $height = $font->getLineHeight();
        $stringHeight = ($height / $font->getUnitsPerEm()) * $fontSize;

        return $stringHeight;
    }

    public function getPdf($invoices = null) {
        #return parent::getPdf($params);

        $this->_beforeGetPdf();
        $this->_initRenderer('invoice');

        foreach ($invoices as $invoice) {
            $this->current_invoice = $invoice;

            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->emulate($invoice->getStoreId());
            }

            $this->_invoice = $invoice;

            $settings = new Varien_Object();
            $order = $invoice->getOrder();

            $settings->setStore($invoice->getStore());

            #$page = $this->newPage();
            $page = $this->newPage($settings->toArray());
            $font = $this->_setFontRegular($page);
            $this->insertFooter($page);

            $this->_setFontRegular($page);

            /* Add image */
            $this->insertLogo($page, $invoice->getStore());

            $this->current_invoice_store = $invoice->getStore();
            $this->y = self::PAGE_Y_AFTER_LOGO;

            /* Add head */
            $this->insertOrder($page, $order, true);

            $this->setSubject($page, Mage::helper('sales')->__('Invoice'));

            $font = $this->_setFontRegular($page);

            $this->y -= 20;

            $this->drawItemsTitle($page);

            #$this->y -= 20;

            $this->current_item_x = 1;

            /* Add body */
            foreach ($invoice->getAllItems() as $item) {
                if ($item->getOrderItem()->getParentItem()) {
                    continue;
                }

                /* Draw item */
                $page = $this->_drawItem($item, $page, $order);
                $this->current_item_x++;
            }

            $this->insertTotals($page, $invoice);

            $font = $this->_setFontRegular($page);

            $this->y -= 20;

            $this->insertBillsafeDetails($page, $order->getPayment(), $order);

            if ($invoice->getStoreId()) {
                Mage::app()->getLocale()->revert();
            }
        }

        $this->_afterGetPdf();
        return $this->_pdf;
    }

    private function drawItemsTitle(&$page) {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.9));
        $page->drawRectangle(85, $this->y, 555, $this->y - 20, Zend_Pdf_Page::SHAPE_DRAW_FILL);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $this->y -= 12;

        $renderer = Mage::getModel("billsafe/itemrenderer_default");
        $renderer->setPdf($this);
        $renderer->drawHeader($page);
    }

    /**
     * Draw Item process
     *
     * @param  Varien_Object $item
     * @param  Zend_Pdf_Page $page
     * @param  Mage_Sales_Model_Order $order
     * @return Zend_Pdf_Page
     */
    protected function _drawItem(Varien_Object $item, Zend_Pdf_Page $page, Mage_Sales_Model_Order $order) {
        $type = $item->getOrderItem()->getProductType();
        $renderer = $this->_getRenderer($type);

        if ($renderer instanceof Mage_Sales_Model_Order_Pdf_Items_Invoice_Default) {
            $renderer = Mage::getModel("billsafe/itemrenderer_default");
        }

        $renderer->setOrder($order);
        $renderer->setItem($item);
        $renderer->setPdf($this);
        $renderer->setPage($page);
        $renderer->setItemPos($this->current_item_x);
        $renderer->setRenderedModel($this);

        $renderer->draw();

        return $renderer->getPage();
    }

    private function drawInvoiceHeader(&$page) {
        $this->_setFontBold($page, 16);

        $page->drawText("Rechnung", self::PAGE_POSITION_LEFT, $this->y, "UTF-8");

        $this->_setFontRegular($page);
    }

    protected function insertOrder(&$page, $order, $putOrderId = true) {
        $this->insertCustomerAddress($page, $order);
        $this->y -= 30;
        $this->drawInvoiceHeader($page);
        $this->insertOrderInfo($page, $order);
        $this->y -= 10;
    }

    /**
     * Insert totals to pdf page
     *
     * @param  Zend_Pdf_Page $page
     * @param  Mage_Sales_Model_Abstract $source
     * @return Zend_Pdf_Page
     */
    protected function insertTotals($page, $source) {
        $order = $source->getOrder();
        $totals = $this->_getTotalsList($source);
        $lineBlock = array(
            'lines' => array(),
            'height' => 15
        );
        foreach ($totals as $total) {
            $total->setOrder($order)
                    ->setSource($source);

            if ($total->canDisplay()) {
                foreach ($total->getTotalsForDisplay() as $totalData) {
                    $lineBlock['lines'][] = array(
                        array(
                            'text' => $totalData['label'],
                            'feed' => 465,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'normal'
                        ),
                        array(
                            'text' => $totalData['amount'],
                            'feed' => 555,
                            'align' => 'right',
                            'font_size' => $totalData['font_size'],
                            'font' => 'normal'
                        ),
                    );
                }
            }
        }

        $this->y -= 20;
        $page = $this->drawLineBlocks($page, array($lineBlock));
        return $page;
    }

    private function insertCustomerAddress(&$page, $order) {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        $store = $this->current_invoice_store;

        $this->_setFontRegular($page, 5);
        $infos = Mage::getStoreConfig("general/imprint");

        $page->drawText($infos["company_first"] . " - " . $infos["street"] . " - " . $infos["zip"]." ".$infos["city"], self::PAGE_POSITION_LEFT, $top, "UTF-8");
        $top -= 20;

        $billing_address = $order->getBillingAddress();
		
        $this->_setFontRegular($page);
				
		$page->drawText($billing_address->_data['company'], self::PAGE_POSITION_LEFT, $top, "UTF-8");
        $top -= 10;
		
        $page->drawText($order->getCustomerGender(), self::PAGE_POSITION_LEFT, $top, "UTF-8");
        $top -= 10;

        $page->drawText($order->getCustomerFirstname() . " " . $order->getCustomerLastname(), self::PAGE_POSITION_LEFT, $top, "UTF-8");
        $top -= 10;

        $page->drawText($billing_address->getData("street"), self::PAGE_POSITION_LEFT, $top, "UTF-8");
        $top -= 20;

        $this->_setFontBold($page, 8);

        $page->drawText($billing_address->getData("postcode") . " " . $billing_address->getData("city"), self::PAGE_POSITION_LEFT, $top, "UTF-8");
        $top -= 20;

        $this->_setFontRegular($page);

        $this->y = ($this->y > $top) ? $top : $this->y;
    }

    private function insertOrderInfo(&$page, $order) {
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0));
        $font = $this->_setFontRegular($page);
        $page->setLineWidth(0);
        $this->y = $this->y ? $this->y : 815;
        $top = $this->y;
        $right2 = 555;

        $shop_infos = Mage::getStoreConfig("general/imprint");
		
		$invocie_infos = Mage::getModel('sales/order_invoice')
            ->getCollection()
            ->addAttributeToFilter('order_id', $order->_data['entity_id'])
            ->getFirstItem();

		$text_order_nr = "Bestellnummer: " . $order->_data['increment_id'];
		$text_invoice_nr = "Rechnungsnummer: " . $invocie_infos->_data['increment_id'];
		//$text_invoice_date = "Rechnungdatum " . Mage::helper("core")->formatDate($invoice->getCreatedAt(), "medium", false);
		
		$text_date = $shop_infos["city"].", den " . Mage::helper("core")->formatDate(date("d-m-Y", strtotime($invocie_infos->_data["created_at"])), "medium", false);
        $text_invoice = "Ihre Bestellung vom " . Mage::helper("core")->formatDate($order->getCreatedAt(), "medium", false) . ", bei " . $shop_infos["web"];

        $page->drawText(trim(strip_tags($text_date)), $this->getAlignRight(trim(strip_tags($text_date)), $right2, 0, $font, 7), $top, "UTF-8");
        $top -= 10;
		$page->drawText(trim(strip_tags($text_order_nr)), $this->getAlignRight(trim(strip_tags($text_order_nr)), $right2, 0, $font, 7), $top, "UTF-8");
        $top -= 10;
		$page->drawText(trim(strip_tags($text_invoice_nr)), $this->getAlignRight(trim(strip_tags($text_invoice_nr)), $right2, 0, $font, 7), $top, "UTF-8");
        $top -= 10;

        $page->drawText(trim(strip_tags($text_invoice)), self::PAGE_POSITION_LEFT, $top, "UTF-8");
        //$top -= 20;

        $this->y = $top;
    }

    private function insertBillsafeDetails(&$page, $payment, $order) {
        $method = $payment->getMethod();
        $top = $this->y;        

        if ($method == AwHh_Billsafe_Model_Installment::CODE || $method == AwHh_Billsafe_Model_Payment::CODE) {
            $infos = $payment->getAdditionalInformation();
            $data = Mage::getSingleton('billsafe/client')->getPaymentInstruction($order);

            if ($method == AwHh_Billsafe_Model_Installment::CODE) {                
                // Installment
                $page->drawText("Wie vereinbart bezahlen Sie diesen Kauf per Ratenzahlung.", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $top -= 10;
                $page->drawText("BillSAFE setzt sich mit Ihnen in Verbindung und sendet Ihnen Ihren Ratenzahlplan zu.", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $top -= 20;
            } else {
                // Invoice
                $words = explode(" ", $data->legalNote);
                $content = "";
                
                for($i=0; $i < count($words); $i++) {
                    $content .= $words[$i] . " ";
                    
                    if($i % 12 == 0) {
                        $page->drawText($content, self::PAGE_POSITION_LEFT, $top, "UTF-8");
                        $top -= 10;
                        
                        $content = "";
                    }
                }
                
                $top -= 20;
            }

            if ($method == AwHh_Billsafe_Model_Payment::CODE) {
                // Invoice
				$shop_infos = Mage::getStoreConfig("general/imprint");
				
                $page->drawText("Empfänger:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($infos["Recipient"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 10;

                $page->drawText("Kontonr.:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($infos["AccountNumber"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 10;

                $page->drawText("BLZ:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($infos["BankCode"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 10;

                $page->drawText("Bank:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($infos["BankName"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 10;
                
                if (isset($infos["Bic"]) && isset($infos["Iban"])) {
                    $page->drawText("BIC:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                    $page->drawText($infos["Bic"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                    $top -= 10;

                    $page->drawText("IBAN:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                    $page->drawText($infos["Iban"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                    $top -= 10;
                }

                $page->drawText("Betrag:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($infos["Amount"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 10;

                $page->drawText("Verwendungszweck 1:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($infos["Reference"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 10;

                $page->drawText("Verwendungszweck 2:", self::PAGE_POSITION_LEFT, $top, "UTF-8");
                $page->drawText($shop_infos["web"], self::PAGE_POSITION_LEFT + 100, $top, "UTF-8");
                $top -= 20;

                $page->drawText($infos["Note"], self::PAGE_POSITION_LEFT, $top, "UTF-8");
            } else {
                // Installment
            }

            $this->y = $top - 20;
        } else {
			$page->drawText("Zahlungsart: ".$method , self::PAGE_POSITION_LEFT, $top, "UTF-8");
		}
    }

    private function insertFooter(&$page) {
        $this->_setFontRegular($page, 8);
        $page->setFillColor(new Zend_Pdf_Color_GrayScale(0.3));

        $infos = Mage::getStoreConfig("general/imprint");

        $left = 90;
        $top = 70;

        $page->drawText($infos["company_first"], $left, $top, "UTF-8");
        $page->drawText($infos["street"], $left, $top - 10, "UTF-8");
        $page->drawText($infos["zip"] . " " . $infos["city"], $left, $top - 20, "UTF-8");

        $left = 220;

        $page->drawText("Tel.: ".$infos["telephone"], $left, $top, "UTF-8");
        $page->drawText("Fax: ". $infos["fax"], $left, $top - 10, "UTF-8");
        $page->drawText("Web: ".$infos["web"], $left, $top - 20, "UTF-8");
		$page->drawText("Email: ".$infos["email"], $left, $top - 30, "UTF-8");

        $left = 350;

        $page->drawText("Handelregister: ".$infos["register_number"], $left, $top, "UTF-8");
		$page->drawText("Zuständiges Gericht: ".$infos["court"], $left, $top - 10, "UTF-8");
        $page->drawText("Steuernummer: " . $infos["tax_number"], $left, $top - 20, "UTF-8");
		$page->drawText("Ust. Nr.: " . $infos["vat_id"], $left, $top - 30, "UTF-8");
		$page->drawText("Geschäftsführer: " . $infos["ceo"], $left, $top - 40, "UTF-8");
		
    }

    /**
     * Set regular font
     *
     * @param Zend_Pdf_Page $object Page to set font for
     * @param integer       $size   Size to set
     *
     * @return Zend_Pdf_Font
     */
    protected function _setFontRegular($object, $size = 7) {
        $font = $this->getFont();
        $object->setFont($font, $size);
        return $font;
    }

    private function getFont($type = 'normal', $store = '') {
        switch ($type) {
            case 'bold':
                $font = Zend_Pdf_Font::FONT_HELVETICA_BOLD;
                break;
            case 'italic':
                $font = Zend_Pdf_Font::FONT_HELVETICA_ITALIC;
                break;
            default:
                $font = Zend_Pdf_Font::FONT_HELVETICA;
                break;
        }

        return Zend_Pdf_Font::fontWithName($font);
    }

}