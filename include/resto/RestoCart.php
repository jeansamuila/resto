<?php

/*
 * RESTo
 * 
 * RESTo - REstful Semantic search Tool for geOspatial 
 * 
 * Copyright 2013 Jérôme Gasperi <https://github.com/jjrom>
 * 
 * jerome[dot]gasperi[at]gmail[dot]com
 * 
 * This software is governed by the CeCILL-B license under French law and
 * abiding by the rules of distribution of free software.  You can  use,
 * modify and/ or redistribute the software under the terms of the CeCILL-B
 * license as circulated by CEA, CNRS and INRIA at the following URL
 * "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and  rights to copy,
 * modify and redistribute granted by the license, users are provided only
 * with a limited warranty  and the software's author,  the holder of the
 * economic rights,  and the successive licensors  have only  limited
 * liability.
 *
 * In this respect, the user's attention is drawn to the risks associated
 * with loading,  using,  modifying and/or developing or reproducing the
 * software by the user in light of its specific status of free software,
 * that may mean  that it is complicated to manipulate,  and  that  also
 * therefore means  that it is reserved for developers  and  experienced
 * professionals having in-depth computer knowledge. Users are therefore
 * encouraged to load and test the software's suitability as regards their
 * requirements in conditions enabling the security of their systems and/or
 * data to be ensured and,  more generally, to use and operate it in the
 * same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL-B license and that you accept its terms.
 * 
 */

class RestoCart{
    
    /*
     * Context
     */
    public $context;
    
    /*
     * Owner of the cart
     */
    public $user;
    
    /*
     * Cart items 
     *  array(
     *      'url' //
     *      'size'
     *      'checksum'
     *      'mimeType'
     *  )
     */
    private $items = array();
    
    /**
     * Constructor
     * 
     * @param RestoUser $user
     * @param RestoContext $context
     */
    public function __construct($user, $context, $synchronize = false){
        $this->user = $user;
        $this->context = $context;
        if ($synchronize) {
            $this->items = $this->context->dbDriver->get(RestoDatabaseDriver::CART_ITEMS, array('email' => $this->user->profile['email']));
        }
    }
    
    /**
     * Add items to cart
     * 
     * $data should be an array of array.
     * 
     * Structure :
     *      array(
     *          array(
     *              'id' => //featureidentifier
     *             'properties' => array(
     *              
     *              )
     *          ),
     *          array(
     * 
     *          ),
     *          ...
     *      )
     * 
     * @param array $data
     * @param boolean $synchronize : true to synchronize with database
     */
    public function add($data, $synchronize = false) {
        
        if (!is_array($data)) {
            return false;
        }
                    
        $items = array();
        for ($i = count($data); $i--;) {
                    
            if (!isset($data[$i]['id'])) {
                continue;
            }
            
            /*
             * Same resource cannot be added twice
             */
            $itemId = sha1($this->user->profile['email'] . $data[$i]['id']);
            if (isset($this->items[$itemId])) {
                throw new Exception('Cannot add item : ' . $itemId . ' already exist', 1000);
            }   
            
            if ($synchronize) {
                if (!$this->context->dbDriver->store(RestoDatabaseDriver::CART_ITEM, array('email' => $this->user->profile['email'], 'item' => $data[$i]))) {
                    return false;
                }
            }
            $this->items[$itemId] = $data[$i];
            $items[$itemId] = $data[$i];
        }
        
        return $items;
    }
    
    /**
     * Update item in cart
     * 
     * @param string $itemId
     * @param array $item
     * @param boolean $synchronize : true to synchronize with database
     */
    public function update($itemId, $item, $synchronize = false) {
        if (!isset($itemId)) {
            return false;
        }
        if (!isset($this->items[$itemId])) {
            throw new Exception('Cannot update item : ' . $itemId . ' does not exist', 1001);
        }
        if ($synchronize) {
            $this->items[$itemId] = $item;
            return $this->context->dbDriver->update(RestoDatabaseDriver::CART_ITEM, array('email' => $this->user->profile['email'], 'itemId' => $itemId, 'item' => $item));
        }
        else {
            $this->items[$itemId] = $item;
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove item from cart
     * 
     * @param string $itemId
     * @param boolean $synchronize : true to synchronize with database
     */
    public function remove($itemId, $synchronize = false) {
        if (!isset($itemId)) {
            return false;
        }
        if ($synchronize) {
            if (isset($this->items[$itemId])) {
                unset($this->items[$itemId]);
            }
            return $this->context->dbDriver->remove(RestoDatabaseDriver::CART_ITEM, array('email' => $this->user->profile['email'], 'itemId' => $itemId));
        }
        else if (isset($this->items[$itemId])) {
            unset($this->items[$itemId]);
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove all items from cart
     */
    public function clear() {
        $this->items = array();
    }
    
    /**
     * Returns all items from cart
     */
    public function getItems() {
        return $this->items;
    }
    
    /**
     * Return the cart as a JSON file
     * 
     * @param boolean $pretty
     */
    public function toJSON($pretty) {
        return RestoUtil::json_format($this->getItems(), $pretty);
    }

}
