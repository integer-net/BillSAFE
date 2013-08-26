<?php

class AwHh_Billsafe_Model_Itemrenderer_Default extends Mage_Sales_Model_Order_Pdf_Items_Abstract {

    private $pos1 = 90;
    private $pos2 = 110;
    private $pos3 = 290;
    private $pos4 = 395;
    private $pos5 = 435;
    private $pos6 = 495;
    private $pos7 = 550;
    
    public function drawHeader($page) {
        $feedPrice = $this->pos4;
        $feedSubtotal = $this->pos7;
        
        
        
        $lines[0] = array(array(
                'text' => Mage::helper('core/string')->str_split("Art-Nr.", 35, true, true),
                'feed' => $this->pos2, // 35
                'font_size' => 7
                ));
        
        $lines[0][] = array(
                'text' => "Pos",
                'feed' => $this->pos1, // 35
                'font_size' => 7
                );

        // draw SKU
        $lines[0][] = array(
            'text' => Mage::helper('core/string')->str_split("Bezeichnung", 17),
            'feed' => $this->pos3,
            'align' => 'right',
            'font_size' => 7
        );

        // draw QTY
        $lines[0][] = array(
            'text' => "Menge",
            'feed' => $this->pos5,
            'align' => 'right',
            'font_size' => 7
        );

        // draw Price
        $lines[0][] = array(
            'text' => "EP",
            'feed' => $feedPrice,
            'font' => 'bold',
            'align' => 'right',
            'font_size' => 7
        );
        // draw Subtotal
        $lines[0][] = array(
            'text' => "Gesamt",
            'feed' => $feedSubtotal,
            'font' => 'bold',
            'align' => 'right',
            'font_size' => 7
        );

        // draw Tax
        $lines[0][] = array(
            'text' => "Steuer",
            'feed' => $this->pos6,
            'font' => 'bold',
            'align' => 'right',
            'font_size' => 7
        );

        $lineBlock = array(
            'lines' => $lines,
            'height' => 20
        );

        $page = $this->getPdf()->drawLineBlocks($page, array($lineBlock));
        #$this->setPage($page);
    }

    /**
     * Draw item line
     */
    public function draw() {
        $order = $this->getOrder();
        $item = $this->getItem();
        $pdf = $this->getPdf();
        $page = $this->getPage();
        $lines = array();

        // draw Product name
        $lines[0] = array(array(
                'text' => Mage::helper('core/string')->str_split($item->getName(), 35, true, true),
                'feed' => $this->pos2, // 35
                'font_size' => 7
                ));

        // draw SKU
        $lines[0][] = array(
            'text' => $this->getItemPos(),
            'feed' => $this->pos1,
            'font_size' => 7
        );
        
        // draw SKU
        $lines[0][] = array(
            'text' => Mage::helper('core/string')->str_split($this->getSku($item), 17),
            'feed' => $this->pos3,
            'align' => 'right',
            'font_size' => 7
        );

        // draw QTY
        $lines[0][] = array(
            'text' => $item->getQty() * 1,
            'feed' => $this->pos5,
            'align' => 'right',
            'font_size' => 7
        );

        // draw item Prices
        $i = 0;
        $prices = $this->getItemPricesForDisplay();
        $feedPrice = $this->pos4;
        $feedSubtotal = $this->pos7;
        
        foreach ($prices as $priceData) {
            if (isset($priceData['label'])) {
                // draw Price label
                $lines[$i][] = array(
                    'text' => $priceData['label'],
                    'feed' => $feedPrice,
                    'align' => 'right',
                    'font_size' => 7
                );
                // draw Subtotal label
                $lines[$i][] = array(
                    'text' => $priceData['label'],
                    'feed' => $feedSubtotal,
                    'align' => 'right',
                    'font_size' => 7
                );
                $i++;
            }
            // draw Price
            $lines[$i][] = array(
                'text' => $priceData['price'],
                'feed' => $feedPrice,
                'font' => 'bold',
                'align' => 'right',
                'font_size' => 7
            );
            // draw Subtotal
            $lines[$i][] = array(
                'text' => $priceData['subtotal'],
                'feed' => $feedSubtotal,
                'font' => 'bold',
                'align' => 'right',
                'font_size' => 7
            );
            $i++;
        }

        // draw Tax
        $lines[0][] = array(
            'text' => $order->formatPriceTxt($item->getTaxAmount()),
            'feed' => $this->pos6,
            'font' => 'bold',
            'align' => 'right',
            'font_size' => 7
        );

        // custom options
        $options = $this->getItemOptions();
        if ($options) {
            foreach ($options as $option) {
                // draw options label
                $lines[][] = array(
                    'text' => Mage::helper('core/string')->str_split(strip_tags($option['label']), 40, true, true),
                    'font' => 'italic',
                    'feed' => 35,
                    'font_size' => 7
                );

                if ($option['value']) {
                    if (isset($option['print_value'])) {
                        $_printValue = $option['print_value'];
                    } else {
                        $_printValue = strip_tags($option['value']);
                    }
                    $values = explode(', ', $_printValue);
                    foreach ($values as $value) {
                        $lines[][] = array(
                            'text' => Mage::helper('core/string')->str_split($value, 30, true, true),
                            'feed' => 40,
                            'font_size' => 7
                        );
                    }
                }
            }
        }

        $lineBlock = array(
            'lines' => $lines,
            'height' => 20
        );

        $page = $pdf->drawLineBlocks($page, array($lineBlock), array('table_header' => true));
        $this->setPage($page);
    }

}