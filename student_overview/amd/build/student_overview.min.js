// amd/src/student_overview.js

define(['jquery', 'core/chartjs'], function($, Chart) {
    return {
        init: function() {
            var ctx = document.getElementById('learningTimeChart').getContext('2d');
            var learningTimeChart;

            // Function to load chart data
            function loadChartData(timePeriod) {
                $.ajax({
                    url: M.cfg.wwwroot + '/blocks/student_overview/ajax.php',
                    method: 'GET',
                    data: { time_period: timePeriod },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if (learningTimeChart) {
                            learningTimeChart.destroy();
                        }
                        learningTimeChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: data.labels,
                                datasets: [{
                                    label: 'Learning Time (hours)',
                                    data: data.values,
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    borderColor: 'rgba(75, 192, 192, 1)',
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    }
                });
            }

            // Initial load (default: current week)
            loadChartData('current_week');

            // Handle dropdown change
            $('#timePeriod').change(function() {
                var selectedPeriod = $(this).val();
                loadChartData(selectedPeriod);
            });
        }
    };
});