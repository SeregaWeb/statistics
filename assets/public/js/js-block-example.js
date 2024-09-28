/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	// The require scope
/******/ 	var __webpack_require__ = {};
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
/*!********************************************!*\
  !*** ./src/js/acf-blocks/block-example.ts ***!
  \********************************************/
__webpack_require__.r(__webpack_exports__);
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
//# sourceMappingURL=js-block-example.js.map