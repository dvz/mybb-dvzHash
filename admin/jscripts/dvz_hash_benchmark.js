(function (jQuery, lang, my_post_key) {
    const $form = document.querySelector('#benchmarkForm').closest('form');
    const $formButton = $form.querySelector('input[type="submit"]');
    const $results = document.querySelector('.benchmark__results');
    const $graph = $results.querySelector('.benchmark__results__graph');
    const $text = $results.querySelector('.benchmark__results__text pre code');

    const runBenchmarkAction = function (e) {
        e.preventDefault();

        if (!$form.reportValidity()) {
            return;
        }

        document.body.style.cursor =  'progress';
        $formButton.style.cursor = 'progress';
        $formButton.disabled = true;
        $results.style.opacity =  0.5;

        ajaxAction('benchmark', {
            method: 'POST',
            body: new FormData($form),
        }).then(([response, jsonData]) => {
            $text.innerHTML = jsonData.resultsText;

            if (jsonData.hasOwnProperty('results')) {
                let imageWidth = document.querySelector('#inner').offsetWidth - 20;

                const graphFormData = new FormData();
                graphFormData.append('my_post_key', my_post_key);
                graphFormData.append('width', imageWidth.toString());
                graphFormData.append('data', JSON.stringify(jsonData.results));

                ajaxAction('graph', {
                    method: 'POST',
                    body: graphFormData,
                }).then(([response, jsonData]) => {
                    response.blob().then(function (blob) {
                        const image = URL.createObjectURL(blob);

                        $graph.setAttribute('src', image);
                        $graph.hidden = false;

                        $results.hidden = false;
                        $results.style.opacity = 1;
                    });
                }).catch(handleError);
            } else {
                $graph.hidden = true;
                $results.removeAttribute('hidden');
                $results.style.opacity = 1;
            }
        }).catch(handleError).finally(() => {
            document.body.style.cursor = 'auto';
            $formButton.style.removeProperty('cursor');
            $formButton.disabled = false;
        });

        return false;
    };

    const copyResultsText = function () {
        const writeTextContentToClipboard = (element, usingRange) => {
            return new Promise((resolve, reject) => {
                if (usingRange) {
                    const range = document.createRange();

                    range.selectNode(element);
                    window.getSelection().removeAllRanges();
                    window.getSelection().addRange(range);

                    if (document.execCommand('copy')) {
                        window.getSelection().removeAllRanges();
                        resolve();
                    } else {
                        reject();
                    }
                } else {
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(element.textContent.trim()).then(() => {
                            resolve();
                        }).catch(() => {
                            reject();
                        });
                    } else {
                        reject();
                    }
                }
            });
        };

        writeTextContentToClipboard($text).catch(() => {
            return writeTextContentToClipboard($text, true);
        }).then(() => {
            jQuery.jGrowl(lang.dvz_hash_admin_copied, { theme: 'jgrowl_success' });
        });
    };

    const ajaxAction = function (action, options) {
        return new Promise((resolve, reject) => {
            fetch(new Request('index.php?module=tools-dvz_hash&action=benchmark&ajax=1&ajax_action=' + action, options)).then(response => {
                if (response.status === 200) {
                    const contentTypeHeader = response.headers.get('content-type');
                    if (contentTypeHeader !== null) {
                        if (contentTypeHeader.includes('text/html')) {
                            reject();
                        } else if (contentTypeHeader.includes('application/json')) {
                            response.json().then(jsonData => {
                                if (jsonData.hasOwnProperty('errors')) {
                                    reject(jsonData.errors.join('<br />'));
                                } else {
                                    resolve([response, jsonData]);
                                }
                            });
                        } else {
                            resolve([response, null]);
                        }
                    } else {
                        reject();
                    }
                } else {
                    reject();
                }
            }).catch(() => {
                reject();
            });
        });
    };

    const handleError = function (error) {
        let message;

        if (typeof error === 'undefined') {
            message = lang.unknown_error;
        } else {
            message = error;
        }

        jQuery.jGrowl(message, { theme: 'jgrowl_error' });
    };

    document.querySelector('#benchmarkForm').addEventListener('submit', runBenchmarkAction);
    document.querySelector('.benchmark__controls__control--select').addEventListener('click', copyResultsText);
})(jQuery, lang, my_post_key);
