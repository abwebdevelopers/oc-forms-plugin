/**
 * name: jQuery callback
 * author: AB Web Developers (https://github.com/abwebdevelopers)
 * version: 1.0.0
 * 
 * A simple script that allows binding jQuery-dependent callbacks at any point during a page
 * load. This should eliminate any "Uncaught ReferenceError: $ is not defined" errors
 * with minimal overhead
 */

// Register jQuery callback
window.jqueryOnLoadCallbacks = [];
window.jqueryOnLoadCallbacksRun = false;

// Entry point for callbacks - either queued or immediately run, depending on jQuery state
window.jqueryLoaded = function (callback) {
    if (window.jQuery !== undefined) {
        callback.call(document, window.jQuery);
    } else {
        window.jqueryOnLoadCallbacks.push(callback);
    }
}

// Look for jQuery
document.addEventListener("DOMContentLoaded", function() {
    // start from 0 (index), check every 100ms (pause), until 5s (max), do jQuery stuff (check)
    var jqi = 0, jqp = 100, jqm = 5, jqc = setInterval(function () {
        if (window.jqueryOnLoadCallbacksRun) {
            clearInterval(jqc);
            return;
        }

        if (window.jQuery !== undefined) {
            window.jqueryOnLoadCallbacksRun = true;
            $ = window.jQuery;
            var i = 0, max = window.jqueryOnLoadCallbacks.length;

            for (i = 0; i < max; i++) {
                window.jqueryOnLoadCallbacks[i].call(document, $);
            }

            clearInterval(jqc);
            return;
        }

        if (jqi > ((jqm * 1000) / jqp)) {
            console.log('jQuery could not be loaded. Have you added "{% framework extras %}" to your footer partial?');
            clearInterval(jqc);
        }

        jqi++;
    }, jqp);
});
