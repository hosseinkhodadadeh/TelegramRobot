<?php
/**
 * RSA module.
 *
 * This file is part of MadelineProto.
 * MadelineProto is free software: you can redistribute it and/or modify it under the terms of the GNU Affero General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 * MadelineProto is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU Affero General Public License for more details.
 * You should have received a copy of the GNU General Public License along with MadelineProto.
 * If not, see <http://www.gnu.org/licenses/>.
 *
 * @author    Daniil Gentili <daniil@daniil.it>
 * @copyright 2016-2018 Daniil Gentili <daniil@daniil.it>
 * @license   https://opensource.org/licenses/AGPL-3.0 AGPLv3
 *
 * @link      https://docs.madelineproto.xyz MadelineProto documentation
 */

namespace danog\MadelineProto;

class RSA
{
    use \danog\MadelineProto\TL\TL;
    use \danog\MadelineProto\Tools;
    use \danog\Serializable;
    public $e;
    public $n;
    public $fp;

    public function __magic_construct($rsa_key)
    {
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['rsa_init'], Logger::ULTRA_VERBOSE);
        $key = new \phpseclib\Crypt\RSA();
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['loading_key'], Logger::ULTRA_VERBOSE);
        $key->load($rsa_key);
        $this->n = \phpseclib\Common\Functions\Objects::getVar($key, 'modulus');
        $this->e = \phpseclib\Common\Functions\Objects::getVar($key, 'exponent');
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['computing_fingerprint'], Logger::ULTRA_VERBOSE);
        $this->fp = substr(sha1($this->serialize_object(['type' => 'bytes'], $this->n->toBytes(), 'key').$this->serialize_object(['type' => 'bytes'], $this->e->toBytes(), 'key'), true), -8);

        return true;
    }

    public function __sleep()
    {
        return ['e', 'n', 'fp'];
    }

    public function encrypt($data)
    {
        \danog\MadelineProto\Logger::log(\danog\MadelineProto\Lang::$current_lang['rsa_encrypting'], Logger::VERBOSE);

        return (new \phpseclib\Math\BigInteger($data, 256))->powMod($this->e, $this->n)->toBytes();
    }
}
