/**
 * @file
 */

(function (Drupal) {
  "use strict";

  Drupal.behaviors.twitterJs = {
    attach: function (context) {
      if (jQuery(".twitter-timeline")[0]) {
        var script = document.createElement("script");
        script.src = '//platform.twitter.com/widgets.js';
        document.head.appendChild(script);
      }
    }
  };

})(Drupal);
