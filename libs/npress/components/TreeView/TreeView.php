<?php
/**
 * TreeView Control
 *
 * @author        Blonďák <blondak@neser.cz>
 * @copyright  Copyright (c) 2009 Blonďák <blondak@neser.cz>
 * @version		1.0.1
 *
 */

//interface ITreeViewNode{
//	/**
//	 * Return all avaible child Nodes
//	 * @return ITreeViewNode
//	 */
//	public function getChildNodes();
//
//	/**
//	 * Checks if current node has ChildNodes
//	 * @return bool
//	 */
//	public function hasChildNodes();
//
//	/**
//	 *
//	 * @return string
//	 */
//	public function getNodeCaption();
//}
//
//interface IExpandableTreeViewNode {
//	/**
//	 *
//	 * @return int
//	 */
//	public function getNodeId();
//}

class TreeView extends Control
{
  /******************** variables ********************/

  /** @var bool */
  public $useAjax = true;

  /**
   * Hide root node?
   *
   *  @var bool */
  public $hideRootNode = false;

  /** @var ITreeViewNode*/
  protected $treeViewNode;

  /** @var event */
  public $onRenderNode;

  /**
   *
   * @param ITreeViewNode
   * @return ITreeViewNode
   */
  public function treeViewNode($tvn = null)
  {
    if ($tvn === null) {
      $tvn = $this->treeViewNode;
    } elseif (
      !(is_object($tvn) && in_array('ITreeViewNode', class_implements($tvn)))
    ) {
      throw new MemberAccessException(
        "TreeViewNode must implement ITreeViewNode interface."
      );
    }
    return $this->treeViewNode = $tvn;
  }

  public function renderNode(&$node)
  {
    if (!empty($this->onRenderNode)) {
      $this->onRenderNode($node);
    } else {
      echo htmlSpecialChars($node->getNodeCaption());
    }
  }

  protected function loadTemplateFile(&$template)
  {
    $template->setFile(dirname(__FILE__) . '/TreeView.latte');
  }

  public function render()
  {
    $template = $this->template;
    $this->loadTemplateFile($template);
    $template->node = $this->treeViewNode;
    $template->isRootNode = true;
    $template->firstRun = true;
    $template->render();
  }
}

class ExpandableTreeView extends TreeView
{
  /** @persistent array */
  public $expandedNodes = null;

  /**
   *
   * @param ITreeViewNode, IExpandableTreeViewNode
   * @return ITreeViewNode, IExpandableTreeViewNode
   */
  public function treeViewNode($tvn = null)
  {
    if ($tvn === null) {
      $tvn = $this->treeViewNode;
    } else {
      $impl = class_implements($tvn);
      if (
        !(
          is_object($tvn) &&
          in_array('ITreeViewNode', $impl) &&
          in_array('IExpandableTreeViewNode', $impl)
        )
      ) {
        throw new MemberAccessException(
          "TreeViewNode must implement ITreeViewNode and IExpandableTreeViewNode interface."
        );
      }
    }
    return $this->treeViewNode = $tvn;
  }

  protected function loadTemplateFile(&$template)
  {
    $template->setFile(dirname(__FILE__) . '/ExpandableTreeView.latte');
  }

  /**
   * Checks if $Node is Expanded
   * @param ITreeViewNode, IExpandableTreeViewNode || int
   * @return bool
   */
  public function isNodeExpanded($Node)
  {
    if ($this->expandedNodes === null) {
      return false;
    }
    if (is_object($Node)) {
      return in_array($Node->getNodeId(), $this->expandedNodes);
    }
    return in_array($Node, $this->expandedNodes);
  }

  /**
   * Expands selected $Node
   * @param ITreeViewNode, IExpandableTreeViewNode || int
   * @return void
   */
  public function expandNode($Node = null)
  {
    if ($this->expandedNodes === null) {
      $this->expandedNodes = array();
    }
    if ($Node === null) {
      $Node = $this->treeViewNode;
    }
    if ($this->isNodeExpanded($Node)) {
      return;
    }
    $this->expandedNodes[] = is_object($Node) ? $Node->getNodeId() : $Node;
  }

  /**
   * Colapse selected $Node
   * @param ITreeViewNode, IExpandableTreeViewNode || int
   * @return void
   */
  public function colapseNode($Node)
  {
    if ($this->expandedNodes === null) {
      return;
    }
    if (!$this->isNodeExpanded($Node)) {
      return;
    }
    $this->expandedNodes = array_diff($this->expandedNodes, array(
      is_object($Node) ? $Node->getNodeId() : $Node
    ));
  }

  /**
   * Expands all nodes
   * @param ITreeViewNode, IExpandableTreeViewNode
   * @param bool
   * @return void
   */
  public function expandAll($rootNode = null, $ClearNodes = true)
  {
    if ($ClearNodes) {
      $this->expandedNodes = array();
    }
    if ($rootNode === null) {
      $rootNode = $this->treeViewNode;
    }
    $this->expandedNodes[] = $rootNode->getNodeId();
    if ($this->treeViewNode->hasChildNodes()) {
      foreach ($rootNode->getChildNodes() as $node) {
        $this->expandAll($node, false);
      }
    }
  }

  /**
   * Colapse all nodes
   * @return void
   */
  public function colapseAll()
  {
    $this->expandedNodes = null;
  }

  public function handleExpandNode($id)
  {
    $this->invalidateControl();
    $this->expandNode($id);
  }

  public function handleColapseNode($id)
  {
    $this->invalidateControl();
    $this->colapseNode($id);
  }
}
