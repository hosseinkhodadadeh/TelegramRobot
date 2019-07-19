<?php

/**
 * SocketReader module.
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

namespace danog\MadelineProto\Threads;

/**
 * Manages packing and unpacking of messages, and the list of sent and received messages.
 */
class SocketReader extends \Threaded implements \Collectable
{
    public $ready = false;

    public function __construct($me, $current)
    {
        $this->API = $me;
        $this->current = $current;
    }

    public function __sleep()
    {
        return ['current', 'API', 'garbage'];
    }

    public function __destruct()
    {
        $this->logger->logger(\danog\MadelineProto\Lang::$current_lang['shutting_down_reader_pool'].$this->current, \danog\MadelineProto\Logger::NOTICE);
    }

    /**
     * Reading connection and receiving message from server. Check the CRC32.
     */
    public function run()
    {
        require __DIR__.'/../../../../vendor/autoload.php';
        $handler_pool = new \Pool($this->API->settings['threading']['handler_workers']);
        $this->ready = true;
        while ($this->API->run_workers) {
            try {
                $this->API->datacenter->sockets[$this->current]->reading = true;
                //$this->logger->logger('RECEIVING');
                $error = $this->API->recv_message($this->current);
                $this->logger->logger('NOW HANDLE');
                $handler_pool->submit(new SocketHandler($this->API, $this->current, $error));
                $this->logger->logger('SUBMITTED');
                $this->API->datacenter->sockets[$this->current]->reading = false;
            } catch (\danog\MadelineProto\NothingInTheSocketException $e) {
                //$this->logger->logger('Nothing in the socket for dc '.$this->current, \danog\MadelineProto\Logger::VERBOSE);
            }
        }
        while ($number = $handler_pool->collect()) {
            $this->logger->logger(sprintf(\danog\MadelineProto\Lang::$current_lang['shutting_down_handler_pool'], $this->current, $number), \danog\MadelineProto\Logger::NOTICE);
        }
        $this->setGarbage();
    }

    public $garbage = false;

    public function setGarbage()
    {
        $this->garbage = true;
    }

    public function isGarbage()
    {
        return $this->garbage;
    }
}
