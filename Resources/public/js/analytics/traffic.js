
    function setTraffic(data) {
        $('#data_desktop_traffic .data_visits').html(data.overview.desktopTraffic);
        $('#data_desktop_traffic .data_percentage').html(data.overview.desktopTrafficPercentage);

        $('#data_mobile_traffic .data_visits').html(data.overview.mobileTraffic);
        $('#data_mobile_traffic .data_percentage').html(data.overview.mobileTrafficPercentage);

        $('#data_tablet_traffic .data_visits').html(data.overview.tabletTraffic);
        $('#data_tablet_traffic .data_percentage').html(data.overview.tabletTrafficPercentage);

        var chartData = [
            {
                value: removeNumberFormat(data.overview.desktopTraffic),
                color:"rgba(41, 151, 206, 0.6)"
            },
            {
                value : removeNumberFormat(data.overview.mobileTraffic),
                color : "rgba(41, 200, 150, 0.6)"
            },
            {
                value : removeNumberFormat(data.overview.tabletTraffic),
                color : "rgba(41, 50, 200, 0.6)"
            }
        ]

        var myLine = new Chart(document.getElementById("js-traffic-dashboard-chart").getContext("2d")).Pie(chartData);
    }
