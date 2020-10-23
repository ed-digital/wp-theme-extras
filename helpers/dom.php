<?php
if (!class_exists('HTMLDom')) {
  class Document {
    static public function render ($dom) {

    }
    static public function wrap ($element, $content) {
      $wrapper = is_string($content) ? Document::toElement($content) : $content;
      $element->parentNode->replaceChild($element, $wrapper);
      $wrapper->appendChild($element);
      return $wrapper;
    }
    static public function wrapInner($element, $content) {
      $wrapper = is_string($content) ? Document::toElement($content) : $content;
      while ($element->childNodes->length) {
        $child = $element->childNodes->item(0);
        $element->removeChild($child);
        $wrapper->appendChild($child);
      }
      $element->appendChild($wrapper);
      return $element;
    }
    static public function toElement ($content) {
      return Document::toElements($content)[0];
    }
    static public function toElements ($content) {
      $nodes = [];
      libxml_use_internal_errors(true);
      $dom = new DOMDocument();
      $dom->loadHTML("<html>" . $content . "</html>");
      $child = $d->documentElement->firstChild;
      while($child) {
        $nodes[] = $doc->importNode($child,true);
        $child = $child->nextSibling;
      }
      return $nodes;
    }   
  }
}