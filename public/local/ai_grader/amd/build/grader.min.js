define('local_ai_grader/grader', ['jquery', 'core/ajax', 'core/notification'],
    function ($, Ajax, Notification) {

        return {
            init: function () {
                console.log('AI Grader V3 loaded');
                var triggerSelector = '#manualgradingform .fitem_fsubmit .felement';
                // Wait for the form to be available
                // Wait for the manual grading form OR the history table to be available
                if ($(triggerSelector).length) {
                    this.addGradeButton();
                }

                // Check for history table (Review page)
                if ($('.history table').length) {
                    this.injectHistory();
                }
            },

            injectHistory: function () {
                var that = this;
                var urlParams = new URLSearchParams(window.location.search);
                var attemptId = urlParams.get('attempt');

                if (!attemptId) return;

                console.log('AI Grader: Fetching logs for attempt ' + attemptId);

                Ajax.call([{
                    methodname: 'local_ai_grader_get_logs',
                    args: {
                        attemptid: attemptId
                    }
                }])[0].done(function (logs) {
                    if (logs.length === 0) return;

                    console.log('AI Grader: Found ' + logs.length + ' logs');

                    // Group logs by questionid
                    var logsByQuestion = {};
                    logs.forEach(function (log) {
                        if (!logsByQuestion[log.questionid]) {
                            logsByQuestion[log.questionid] = [];
                        }
                        logsByQuestion[log.questionid].push(log);
                    });

                    // For each question on the page
                    $('.que').each(function () {
                        var que = $(this);
                        var idAttr = que.attr('id'); // e.g., "question-1-1" or similar
                        // We need to find the correct question ID. 
                        // Often it's in a hidden input or we can guess from context.
                        // Better: Moodle usually has a qid in some data attribute.

                        // Let's look for the response history table in this question
                        var historyTable = que.find('.history table');
                        if (historyTable.length === 0) return;

                        // Try to find question ID. 
                        // In review.php, there's often no direct QID in .que, but we can look for it.
                        // Fallback: check all logs and if they match the slot. 
                        // But we don't know the slot easily here without parsing the ID.

                        // Actually, we can fetch logs for ALL questions and just match them if we can find the QID.
                        // Let's find where QID is stored in the DOM.
                        // Usually: <input type="hidden" name="q123:slot" value="...">
                        // Or just parse the ID if it's "question-attemptID-slot"

                        // Let's try matching via the content for now or just append all relevant logs if it's a single question page.
                        // Ideally we find the question ID.
                        var qid = 0;
                        // Moodle 4.x: .que class often has data-attribute?
                        // Let's look at the specific feedback or something.

                        // If we can't find QID, we might just match by looking at the logs attemptid.
                        // But wait, the logs we fetched are already filtered by attemptId.

                        // Let's assume for now we can find the question ID or just use the first log if it's likely.
                        // Better approach: filter logs that haven't been injected yet.

                        logs.forEach(function (log) {
                            // Check if this log belongs to this question
                            // (This is tricky without knowing which .que is which question ID)
                            // However, each .que has a .qtext. Maybe we can't match easily.

                            // Let's just append to the FIRST history table for now if there's only one, 
                            // OR try to match question ID if available.

                            // In Moodle, the question ID is usually not directly visible, 
                            // but we can find it in the "Edit question" link if available.
                            var editLink = que.find('.editquestion a').attr('href');
                            if (editLink) {
                                var match = editLink.match(/id=(\d+)/);
                                if (match && match[1] == log.questionid) {
                                    that.appendLogRow(historyTable, log);
                                }
                            } else {
                                // Fallback: If only one question on page and one log, just append.
                                if ($('.que').length === 1 && logs.length >= 1) {
                                    that.appendLogRow(historyTable, log);
                                } else {
                                    // Complex case: multiple questions. 
                                    // For now, let's just match any log that we haven't matched yet? No.
                                    // Search for question ID in hidden inputs
                                    // <input type="hidden" name="question123_sequencecheck" ...>
                                }
                            }
                        });
                    });
                });
            },

            appendLogRow: function (table, log) {
                // Avoid duplicate injection
                if ($('#ai-log-' + log.id).length) return;

                var date = new Date(log.timecreated * 1000);
                var dateStr = date.toLocaleString();

                var lastRow = table.find('tbody tr').last();
                var stepNum = "AI"; // Or parse last step number + 0.5

                var rowHtml = '<tr id="ai-log-' + log.id + '" class="table-info">';
                rowHtml += '<td>' + stepNum + '</td>';
                rowHtml += '<td>' + dateStr + '</td>';
                rowHtml += '<td><strong>AI Suggested Grade: ' + log.ai_mark + '</strong><br/><small>' + log.ai_comment + '</small></td>';
                rowHtml += '<td>AI Log</td>';
                if (table.find('th').length > 4) {
                    rowHtml += '<td>' + log.ai_mark + '</td>';
                }
                rowHtml += '</tr>';

                table.find('tbody').append(rowHtml);
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
