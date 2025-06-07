// This file is part of the Learning Time Tracker block for Moodle.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Learning Time Tracker block JavaScript module.
 *
 * @module     block_learningtime/learningtime
 * @copyright  2023 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/templates', 'core/notification'], function($, ajax, templates, notification) {
    // Private variables.
    var chart = null;
    var currentRange = 'last7days';
    var userId = 0;

    return {
        /**
         * Initialize the block.
         */
        init: function() {
            // Get the user ID from the block content.
            userId = $('.block_learningtime').data('userid');
            
            // Set up event listeners.
            this.setupEventListeners();
            
            // Load initial data.
            this.loadData(currentRange);
        },
        
        /**
         * Set up event listeners for the block.
         */
        setupEventListeners: function() {
            // Time range button clicks.
            $(document).on('click', '.block_learningtime .time-range-btn', function(e) {
                e.preventDefault();
                var range = $(this).data('range');
                
                // Update active button state.
                $('.block_learningtime .time-range-btn').removeClass('active');
                $(this).addClass('active');
                
                // Load data for the selected range.
                this.loadData(range);
            }.bind(this));
        },
        
        /**
         * Load data for the specified time range.
         * @param {string} range The time range to load data for.
         */
        loadData: function(range) {
            currentRange = range;
            
            // Show loading state.
            this.showLoading();
            
            // Call the external function to get data.
            var promises = ajax.call([
                {
                    methodname: 'block_learningtime_get_learning_time_data',
                    args: {
                        userid: userId,  // Already parsed as int
                        range: range.toString() // Ensure string
                    }
                }
            ]);
            
            promise[0].done(function(response) {
                this.updateChart(response);
                this.updateStats(response);
            }.bind(this)).fail(notification.exception);
        },
        
        /**
         * Show loading state.
         */
        showLoading: function() {
            $('.block_learningtime .chart-container').html(
                '<div class="loading-message">' + M.util.get_string('loading', 'block_learningtime') + '</div>'
            );
            $('.block_learningtime .stats-container').html('');
        },
        
        /**
         * Update the chart with new data.
         * @param {object} data The data to display in the chart.
         */
        updateChart: function(data) {
            // Prepare chart data.
            var labels = [];
            var minutesData = [];
            
            data.days.forEach(function(day) {
                labels.push(day.label);
                minutesData.push(day.minutes);
            });
            
            // Create or update the chart.
            var ctx = $('.block_learningtime .chart-container').html('<canvas></canvas>').find('canvas')[0];
            
            if (chart) {
                // Update existing chart.
                chart.data.labels = labels;
                chart.data.datasets[0].data = minutesData;
                chart.update();
            } else {
                // Create new chart.
                chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: M.util.get_string('minutes', 'block_learningtime'),
                            data: minutesData,
                            backgroundColor: 'rgba(15, 108, 191, 0.1)',
                            borderColor: 'rgba(15, 108, 191, 1)',
                            borderWidth: 2,
                            tension: 0.4,
                            pointBackgroundColor: 'rgba(15, 108, 191, 1)',
                            pointRadius: 3,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: M.util.get_string('minutes', 'block_learningtime')
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.parsed.y.toFixed(1) + ' ' + 
                                            M.util.get_string('minutes', 'block_learningtime');
                                    }
                                }
                            }
                        }
                    }
                });
            }
        },
        
        /**
         * Update the statistics display.
         * @param {object} data The data containing statistics.
         */
        updateStats: function(data) {
            // Prepare the stats data for the template.
            var statsData = {
                total: {
                    label: M.util.get_string('total', 'block_learningtime'),
                    minutes: data.total.minutes,
                    hours: data.total.hours,
                    minutes_label: M.util.get_string('minutes', 'block_learningtime'),
                    hours_label: M.util.get_string('hours', 'block_learningtime')
                },
                average: {
                    label: M.util.get_string('average', 'block_learningtime'),
                    minutes: data.average.minutes,
                    hours: data.average.hours,
                    minutes_label: M.util.get_string('minutes', 'block_learningtime'),
                    hours_label: M.util.get_string('hours', 'block_learningtime')
                }
            };
            
            // Render the stats template.
            templates.render('block_learningtime/stats', statsData)
                .then(function(html) {
                    $('.block_learningtime .stats-container').html(html);
                    return;
                })
                .catch(function(ex) {
                    notification.exception(ex);
                });
        }
    };
});