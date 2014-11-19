<?php
/**
 * Netresearch Billsafe
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @copyright   Copyright (c) 2014 Netresearch GmbH & Co. KG (http://www.netresearch.de/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wrapper for filesystem access.
 *
 * @category    Netresearch
 * @package     Netresearch_Billsafe
 * @author      Christoph Aßmann <christoph.assmann@netresearch.de>
 */
class Netresearch_Billsafe_Model_Io_Settlement extends Varien_Object
{
    /**
     * @return Varien_Io_File
     */
    public function getFileHandler()
    {
        if (!$this->hasData('file_handler')) {
            $this->setData('file_handler', new Varien_Io_File());
        }
        return $this->getData('file_handler');
    }

    /**
     * Write settlement file to disk.
     *
     * @param string $dirname Absolute path to settlement file storage
     * @param string $basename Settlement file basename
     * @param string $data File contents, base64 encoded
     *
     * @throws Exception File I/O exception
     * @throws Varien_Io_Exception File I/O exception
     *
     * @return int|boolean
     */
    public function writeSettlementFile($dirname, $basename, $data)
    {
        $filename = $dirname . DS . $basename;

        $this->getFileHandler()->setAllowCreateFolders(true);
        $this->getFileHandler()->createDestinationDir($dirname);
        return $this->getFileHandler()->write($filename, base64_decode($data));
    }


}
