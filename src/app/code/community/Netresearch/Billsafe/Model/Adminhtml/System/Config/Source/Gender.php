<?php

class Netresearch_Billsafe_Model_Adminhtml_System_Config_Source_Gender
{
    /**
     * Query genders from customer eav source.
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        $genders = Mage::getSingleton('eav/config')
            ->getAttribute('customer', 'gender')
            ->getSource()
            ->getAllOptions(false);

        foreach ($genders as $gender) {
            $options[$gender['label']] = Mage::helper('billsafe/data')->__($gender['label']);
        }

        return $options;
    }
}