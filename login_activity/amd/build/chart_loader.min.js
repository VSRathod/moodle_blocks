define(['jquery', 'core/ajax', 'core/chartjs'], function($, Ajax, Chart) {
    return {
        init: function() {
            $(document).ready(function() {
                let ctx = document.getElementById("loginChart").getContext("2d");
                let chartInstance = new Chart(ctx, {
                    type: "line",
                    data: { labels: [], datasets: [{ label: "Logins", data: [], borderColor: "#007bff", fill: false }] },
                    options: { responsive: true, animation: { duration: 1000 } }
                });

                function fetchData(filter) {
                    $.ajax({
                        url: M.cfg.wwwroot + "/blocks/login_activity/ajax.php",
                        method: "GET",
                        data: { filter: filter },
                        dataType: "json",
                        success: function(response) {
                            let labels = response.map(item => item.label);
                            let values = response.map(item => item.value);

                            chartInstance.data.labels = labels;
                            chartInstance.data.datasets[0].data = values;
                            chartInstance.update();
                        },
                        error: function() {
                            alert("Error loading data.");
                        }
                    });
                }

                $("#filter").change(function() {
                    fetchData($(this).val());
                });

                fetchData("daily");
            });
        }
    };
});
