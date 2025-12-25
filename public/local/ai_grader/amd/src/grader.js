define('local_ai_grader/grader', ['jquery', 'core/ajax', 'core/notification'],
    function ($, Ajax, Notification) {

        return {
            init: function () {
                console.log('AI Grader V3 loaded');

                // Debug indicator
                if ($('#ai-grader-debug-info').length === 0) {
                    $('body').prepend('<div id="ai-grader-debug-info" style="position:fixed; top:0; right:10px; background:rgba(0,0,0,0.8); color:#00ff00; padding:10px; z-index:9999; font-size:12px; border: 1px solid #00ff00; border-radius: 0 0 5px 5px;">AI Grader V3: <span id="ai-debug-status">Initializing...</span></div>');
                }

                var triggerSelector = '#manualgradingform .fitem_fsubmit .felement';
                // Wait for the form to be available
                if ($(triggerSelector).length) {
                    this.addGradeButton();
                }

                // Check for history table (Review page)
                var historyExists = $('.history table').length || $('.responsehistoryheader').length || $('.que .content').length;
                if (historyExists) {
                    console.log('AI Grader: History detected, initiating injection');
                    this.injectHistory();
                }
            },

            updateStatus: function (msg, color) {
                var el = $('#ai-debug-status');
                el.text(msg);
                if (color) el.css('color', color);
            },

            injectHistory: function () {
                var that = this;
                var urlParams = new URLSearchParams(window.location.search);
                var attemptId = urlParams.get('attempt');

                if (!attemptId) {
                    this.updateStatus('No Attempt ID in URL', 'orange');
                    return;
                }

                this.updateStatus('Fetching logs for attempt ' + attemptId + '...');

                Ajax.call([{
                    methodname: 'local_ai_grader_get_logs',
                    args: {
                        attemptid: attemptId
                    }
                }])[0].done(function (logs) {
                    if (logs.length === 0) {
                        that.updateStatus('No logs found for attempt ' + attemptId, 'yellow');
                        return;
                    }

                    that.updateStatus('Found ' + logs.length + ' logs, matching questions...', '#00ff00');

                    // Find Usage ID from DOM
                    var usageId = 0;
                    $('.que').each(function () {
                        var id = $(this).attr('id');
                        if (id && id.indexOf('question-') === 0) {
                            var parts = id.split('-');
                            if (parts.length >= 2) {
                                usageId = parts[1];
                                return false; // break
                            }
                        }
                    });

                    if (!usageId) {
                        console.log('AI Grader: Could not detect usageId, trying fallback');
                    }

                    var injectedCount = 0;
                    logs.forEach(function (log) {
                        var targetElementId = 'question-' + usageId + '-' + log.slot;
                        var targetQue = $('#' + targetElementId);

                        if (targetQue.length === 0) {
                            // Try targeting by slot directly if usageId failed
                            targetQue = $('.que').filter(function () {
                                var id = $(this).attr('id') || '';
                                return id.endsWith('-' + log.slot);
                            });

                            if (targetQue.length === 0 && $('.que').length === 1) {
                                targetQue = $('.que');
                            }
                        }

                        if (targetQue.length) {
                            // Look for the table. It might not be in a div with class 'history' sometimes.
                            var historyTable = targetQue.find('table').filter(function () {
                                var text = $(this).prevAll('h4').text() || $(this).closest('.history').find('h4').text();
                                return text.toLowerCase().indexOf('history') !== -1 || $(this).closest('.history').length > 0;
                            });

                            if (historyTable.length === 0) {
                                // Just find the table in the last part of the question content
                                historyTable = targetQue.find('.history table, .responsehistoryheader table, .content table').last();
                            }

                            if (historyTable.length) {
                                console.log('AI Grader: Injecting log ' + log.id + ' into question slot ' + log.slot);
                                that.appendLogRow(historyTable, log);
                                injectedCount++;
                            }
                        } else {
                            console.log('AI Grader: Target question not found for log ' + log.id + ' (Slot: ' + log.slot + ')');
                        }
                    });

                    that.updateStatus('Injected ' + injectedCount + ' / ' + logs.length + ' logs', injectedCount > 0 ? '#00ff00' : 'red');
                }).fail(function (ex) {
                    that.updateStatus('AJAX Failed', 'red');
                    console.error('AI Grader: Ajax failed', ex);
                });
            },

            appendLogRow: function (table, log) {
                // Avoid duplicate injection
                if ($('#ai-log-' + log.id).length) return;

                var date = new Date(log.timecreated * 1000);
                var dateStr = date.toLocaleString();

                var stepNum = "AI Advice";

                // Find column count to match table header
                var colCount = table.find('thead th, thead td').length;
                if (colCount === 0) colCount = table.find('tr').first().find('th, td').length;

                var rowHtml = '<tr id="ai-log-' + log.id + '" style="background-color: #e7f3ff; border-left: 5px solid #007bff;">';
                rowHtml += '<td>' + stepNum + '</td>';
                rowHtml += '<td>' + dateStr + '</td>';

                // Action column
                var actionContent = '<strong>AI Suggested: ' + log.ai_mark + '</strong><br/>' + log.ai_comment;
                rowHtml += '<td>' + actionContent + '</td>';

                // State column
                rowHtml += '<td>AI Log</td>';

                // Marks column (if exists)
                if (colCount > 4) {
                    rowHtml += '<td>' + log.ai_mark + '</td>';
                }

                // Pad if more columns
                for (var i = 5; i < colCount; i++) {
                    rowHtml += '<td></td>';
                }

                rowHtml += '</tr>';

                // Append to tbody or last tr
                var tbody = table.find('tbody');
                if (tbody.length) {
                    tbody.append(rowHtml);
                } else {
                    table.append(rowHtml);
                }
            },

            addGradeButton: function () {
                var btnHtml = '<input type="button" class="btn btn-secondary ml-2" id="ai-grader-btn" value="Grade with AI V3">';
                $('#id_submitbutton').after(btnHtml);

                $('#ai-grader-btn').on('click', function (e) {
                    e.preventDefault();
                    var that = this;

                    // Get question text and answer
                    var questionText = $('.qtext').text();
                    var studentAnswer = $('.outcome .specificfeedback').text();
                    if (!studentAnswer || studentAnswer.trim() === '') {
                        studentAnswer = $('.answer').text();
                    }

                    // Try to find max mark.
                    var infoBlock = $('.grade').text(); // "Marked out of 10.00"
                    var match = infoBlock.match(/out of\s+([\d\.]+)/);
                    var maxMark = match ? match[1] : 10;

                    // Get attempt and slot from URL
                    var urlParams = new URLSearchParams(window.location.search);
                    var attemptId = urlParams.get('attempt') || 0;
                    var slot = urlParams.get('slot') || 0;

                    $(this).val('Grading...');
                    $(this).prop('disabled', true);

                    console.log('AI Grader V3: Starting grade request...');
                    Ajax.call([{
                        methodname: 'local_ai_grader_grade_essay',
                        args: {
                            question_text: questionText,
                            student_response: studentAnswer,
                            max_mark: maxMark,
                            attemptid: attemptId,
                            slot: slot
                        }
                    }])[0].done(function (data) {
                        console.log('AI Grader: Success callback triggered.', data);

                        // Select fields
                        var markField = $('input[name*="[-mark]"]');
                        var commentField = $('textarea[name*="[-comment]"]');

                        if (markField.length === 0) {
                            markField = $('input[name$="-mark"], input[name$="_mark"]').first();
                        }
                        if (commentField.length === 0) {
                            commentField = $('textarea[name$="-comment"], textarea[name$="_comment"]').first();
                        }

                        // Set values (Default behavior: AI grade is final if saved)
                        markField.val(data.mark);
                        commentField.val(data.comment);

                        if (Y && Y.M && Y.M.editor_atto) {
                            var elementId = $('textarea[name*="[-comment]"]').attr('id');
                            if (elementId) {
                                // Y.M.editor_atto.get_text_editor(elementId).set_text(data.comment); 
                            }
                        }

                        // UI Changes: Hide fields, show AI feedback + Override button
                        // UI Changes: Hide fields, show AI feedback + Override button
                        var feedbackContainer = $('#ai-grader-feedback');

                        // Find containers for Mark and Comment fields
                        // Supports Moodle legacy (fitem), Boost (form-group), and newer Bootstrap (mb-3)
                        var markContainer = markField.closest('.fitem, .form-group, .mb-3');
                        var commentContainer = commentField.closest('.fitem, .form-group, .mb-3');

                        console.log('AI Grader V2: containers found?', markContainer.length, commentContainer.length);

                        if (feedbackContainer.length === 0) {
                            var feedbackHtml = `
                                <div id="ai-grader-feedback" class="alert alert-info mt-3 mb-3">
                                    <h5>AI Feedback</h5>
                                    <p><strong>Mark:</strong> <span id="ai-mark-display"></span> / ${maxMark}</p>
                                    <div id="ai-comment-display" style="white-space: pre-wrap; background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; margin-bottom: 15px;"></div>
                                    <button type="button" class="btn btn-warning mr-2" id="ai-grader-override-btn">Nilai belum sesuai</button>
                                    <button type="button" class="btn btn-success" id="ai-grader-accept-btn">Nilai Sudah Sesuai</button>
                                </div>
                            `;

                            // Insert BEFORE the comment container (or mark container if comment missing)
                            // This ensures it appears exactly where the inputs were.
                            if (commentContainer.length) {
                                commentContainer.before(feedbackHtml);
                            } else if (markContainer.length) {
                                markContainer.before(feedbackHtml);
                            } else {
                                // Fallback: Insert before mark field directly or submit button
                                if (markField.length) {
                                    markField.before(feedbackHtml);
                                } else {
                                    $('#id_submitbutton').closest('.fitem_fsubmit, .form-group, .row').before(feedbackHtml);
                                }
                            }
                        }

                        $('#ai-mark-display').text(data.mark);
                        $('#ai-comment-display').text(data.comment);

                        // Hide standard manual grading controls
                        if (markContainer.length) {
                            markContainer.hide();
                        } else {
                            markField.hide(); // Fallback
                        }

                        if (commentContainer.length) {
                            commentContainer.hide();
                        } else {
                            commentField.hide(); // Fallback
                        }

                        // Show Feedback container
                        $('#ai-grader-feedback').show();

                        // Enable Override
                        $('#ai-grader-override-btn').off('click').on('click', function () {
                            // Show standard fields
                            if (markContainer.length) {
                                markContainer.show();
                            } else {
                                markField.show();
                            }

                            if (commentContainer.length) {
                                commentContainer.show();
                            } else {
                                commentField.show();
                            }

                            // Hide "Nilai belum sesuai" button
                            $(this).hide();
                            $('#ai-grader-accept-btn').hide();
                        });

                        // Enable Accept
                        $('#ai-grader-accept-btn').off('click').on('click', function () {
                            // Visual confirmation
                            $('#ai-grader-feedback h5').append(' <span class="badge badge-success" style="background-color: #28a745; color: white; padding: 3px 7px; border-radius: 4px;">Disetujui</span>');

                            // Hide buttons
                            $(this).hide();
                            $('#ai-grader-override-btn').hide();
                        });



                        $('#ai-grader-btn').val('Grade with AI');
                        $('#ai-grader-btn').prop('disabled', false);
                        // Hide the Grade with AI button since we have a result? Or keep it allows re-grading?
                        // Keep it for now.

                    }).fail(function (ex) {
                        console.error('AI Grader: Failure callback triggered.', ex);
                        Notification.exception(ex);
                        $('#ai-grader-btn').val('Grade with AI');
                        $('#ai-grader-btn').prop('disabled', false);
                    });
                });
            }
        };
    });
