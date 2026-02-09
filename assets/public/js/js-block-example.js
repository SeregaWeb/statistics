/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
var _a;
var initBlockExample = function initBlockExample() {
  var blocks = document.querySelectorAll('.block-example');
  if (blocks) {
    blocks.forEach(function (block) {
      block.classList.add('active');
    });
  }
};
console.log('sdfsdf3');
document.addEventListener('DOMContentLoaded', initBlockExample, false);
if (window['acf']) {
  (_a = window['acf']) === null || _a === void 0 ? void 0 : _a.addAction('render_block_preview', initBlockExample);
}

/******/ })()
;