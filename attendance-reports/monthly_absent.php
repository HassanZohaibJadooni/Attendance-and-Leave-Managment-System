<?php
include "config.php";
include "header.php";
include "sidebar.php";
include "footer.php";

// set default URL and title in variable
$default_url = "lmonth_absent.php";
$default_title = "Last Month Absent Chart";
$default_value_field = "absent_count";
$default_y_axis_title = "Number of Absences";
$default_tooltip_text = "{categoryX}: {valueY} Days Absent";
$default_button_id = "absent_lastmonth";

?>
<style>
    #monthlyDiv {
        width: 100%;
        height: 500px;
        border: 2px solid #36a0e6ff;
    }

    .active-chart-button {
        background-color: #1e00ffff !important;
        color: white !important;
        border-color: #1e00ffff !important;
    }
</style>

<div class="content-wrapper p-4">
    <div class="col d-flex float-end mt-2" style="padding-left: 20px;"><a href="admin_dashboard.php">Home</a></div>
    <div class="col d-flex justify-content-between align-items-center mb-3">

        <h2 class="text-2xl font-semibold m-0" id="chartTitle"><?php echo $default_title; ?></h2>
        <div class="d-flex gap-2">
            <button class="btn btn-sm px-4 py-1 btn-outline-danger rounded-pill shadow-sm active-chart-button" id="absent_lastmonth"
                data-url="lmonth_absent.php"
                data-title="Last month Absent Chart"
                data-value-field="absent_count"
                data-y-axis-title="Number of Absences"
                data-tooltip-text="{categoryX}: {valueY} Days Absent">Absent</button>

            <button class="btn btn-sm px-4 py-1 btn-outline-success rounded-pill shadow-sm" id="present_lastmonth"
                data-url="lmonth_present.php" data-title="Last Month Present Chart"
                data-value-field="present_count"
                data-y-axis-title="Present Days"
                data-tooltip-text="{categoryX}: {valueY} Days Present"> Present </button>


            <button class="btn btn-sm px-4 py-1 btn-outline-warning rounded-pill shadow-sm" id="leave_lastmonth"
                data-url="lmonth_leave.php"
                data-title="Last Month Leave Chart"
                data-value-field="leave_count"
                data-y-axis-title="Leave Days"
                data-tooltip-text="{categoryX}: {valueY} Days Leave">Leave </button>
        </div>

    </div>
    <div id="monthlyDiv"></div>
</div>

<script src="jquerylibrary.js"></script>
<script src="https://cdn.amcharts.com/lib/5/index.js"></script>
<script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
<script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>

<script>
    am5.ready(function() {
        
        var root = null;
        var chart = null;

        // Function to destroy existing chart instance
        function disposeChart() {
            if (root) {
                root.dispose();
                root = null;
                chart = null;
            }
        }

        // function to fetch data and render chart
        function loadChart(url, title, valueYField, yAxisTitle, tooltipText, buttonId) {
            disposeChart(); // Clear old chart before loading new one

            // Update heading
            $('#chartTitle').text(title);

            // Update active button class
            $('.btn').removeClass('active-chart-button');
            $('#' + buttonId).addClass('active-chart-button');

            $.ajax({
                url: url,
                method: "GET",
                dataType: "json",

                success: function(response) {

                    // get employees list andworking days count
                    var employees = response.employees;
                    // workingdays 
                    var workingDays = response.working_days;
                    // create amCharts root
                    root = am5.Root.new("monthlyDiv");
                    // apply theme
                    root.setThemes([am5themes_Animated.new(root)]);
                    // create XY chart
                    chart = root.container.children.push(
                        am5xy.XYChart.new(root, {
                            panX: true,
                            panY: true,
                            wheelX: "panX",
                            wheelY: "zoomX",
                            pinchZoomX: true,
                            paddingLeft: 0,
                            paddingRight: 1
                        })
                    );

                    // Add cursor
                    var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
                    cursor.lineY.set("visible", false);

                    // X-axis setup
                    var xRenderer = am5xy.AxisRendererX.new(root, {
                        minGridDistance: 30
                    });

                    xRenderer.labels.template.setAll({
                        rotation: -90,
                        centerY: am5.p50,
                        centerX: am5.p100,
                        paddingRight: 15
                    });

                    var xAxis = chart.xAxes.push(
                        am5xy.CategoryAxis.new(root, {
                            categoryField: "employee_name",
                            renderer: xRenderer
                        })
                    );

                    // Y-axis setup
                    var yAxisProps = {
                        min: 0,
                        renderer: am5xy.AxisRendererY.new(root, {}),
                        title: am5.Label.new(root, {
                            text: yAxisTitle
                        })
                    };

                    if (workingDays !== undefined) {
                        yAxisProps.max = workingDays;
                        yAxisProps.strictMinMax = true;
                    }

                    var yAxis = chart.yAxes.push(
                        am5xy.ValueAxis.new(root, yAxisProps)
                    );

                    // Create column series
                    var series = chart.series.push(
                        am5xy.ColumnSeries.new(root, {
                            name: title.replace('Last month ', ''), // Use part of title as series name
                            xAxis: xAxis,
                            yAxis: yAxis,
                            valueYField: valueYField,
                            categoryXField: "employee_name",
                            sequencedInterpolation: true,
                            tooltip: am5.Tooltip.new(root, {
                                labelText: tooltipText
                            })
                        })
                    );

                    // style columns
                    series.columns.template.setAll({
                        cornerRadiusTL: 5,
                        cornerRadiusTR: 5,
                        strokeOpacity: 0
                    });

                    series.columns.template.adapters.add("fill", function(fill, target) {
                        return chart.get("colors").getIndex(series.columns.indexOf(target));
                    });

                    series.columns.template.adapters.add("stroke", function(stroke, target) {
                        return chart.get("colors").getIndex(series.columns.indexOf(target));
                    });

                    // Apply data
                    xAxis.data.setAll(employees);
                    series.data.setAll(employees);

                    // Animate chart
                    series.appear(1000);
                    chart.appear(1000, 100);
                },

                error: function(xhr, status, error) {
                    alert("AJAX Error: " + status + " - " + error);
                    disposeChart();
                }
            });
        }

        // default 
        loadChart("<?= $default_url; ?>","<?= $default_title; ?>","<?= $default_value_field; ?>","<?= $default_y_axis_title; ?>","<?= $default_tooltip_text; ?>","<?= $default_button_id; ?>");

        // add click handlers to all buttons
        $('.d-flex.gap-2 button').on('click', function() {
            var url = $(this).data('url');
            var title = $(this).data('title');
            var valueYField = $(this).data('value-field');
            var yAxisTitle = $(this).data('y-axis-title');
            var tooltipText = $(this).data('tooltip-text');
            var buttonId = $(this).attr('id');

            loadChart(url, title, valueYField, yAxisTitle, tooltipText, buttonId);
        });
    });
</script>