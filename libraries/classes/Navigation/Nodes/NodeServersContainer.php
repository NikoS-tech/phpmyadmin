<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\CheckUserPrivileges;

use function _pgettext;

/**
 * Represents a container for database nodes in the navigation tree
 */
class NodeServersContainer extends Node
{
    /**
     * Initialises the class
     *
     * @param string $name An identifier for the new node
     */
    public function __construct($name)
    {
        parent::__construct($name, Node::CONTAINER);
    }
}
