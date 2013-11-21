<?php
/*  
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * Copyright (c) 2013 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 * 
 */


/**
 * Represents a userless Session.
 *
 * @access private
 * @author Joel Bout, <joel@taotesting.com>
 * @package generis
 * @subpackage common_session
 */
class common_session_AnonymousSession implements common_session_StatelessSession
{

    /**
     * (non-PHPdoc)
     * @see common_session_Session::getUserUri()
     */
    public function getUserUri() {
        return null;
    }

    /**
     * (non-PHPdoc)
     * @see common_session_Session::getDataLanguage()
     */
    public function getUserLabel() {
        return __('guest');
    }    
    
    /**
     * (non-PHPdoc)
     * @see common_session_Session::getUserRoles()
     */
    public function getUserRoles() {
        return array(new core_kernel_classes_Resource(INSTANCE_ROLE_ANONYMOUS));
    }
    
    /**
     * (non-PHPdoc)
     * @see common_session_Session::getDataLanguage()
     */
    public function getDataLanguage() {
        return DEFAULT_LANG;
    }
    
    /**
     * (non-PHPdoc)
     * @see common_session_Session::getInterfaceLanguage()
     */
    public function getInterfaceLanguage() {
        return DEFAULT_LANG;
    }
    
    /**
     * (non-PHPdoc)
     * @see common_session_Session::refresh()
     */
    public function refresh() {
        // nothing to do here
    }

}