<!--Sample Canvas Element - You must have this somewhere in the body of the webpage -->
<canvas class="my-4 w-100" id="chart" width="900" height="380"></canvas>



<!--Sample Pie Chart - Paste AFTER <script src="js/bootstrap.bundle.min.js"></script>-->
<script src="js/Chart.min.js"></script>
<script>
	var config = {
		type: 'pie',
		data: {
		  labels: ['Cash','Credit',],
		  datasets: [			  	
			{
				label: 'Payment Type',
				backgroundColor: ["#FFCCCC", "#CCFFCC"],
				data:[72, 28,],
			}, 
		  ]
		},
	};

	// Loads the Data into the Page
	window.onload = function() {
		var ctx = document.getElementById('chart').getContext('2d');
		window.myLine = new Chart(ctx, config);
	};
</script>	




<!--Sample Doughnut Chart - Paste AFTER <script src="js/bootstrap.bundle.min.js"></script>-->
<script src="js/Chart.min.js"></script>
<script>
	var config = {
		type: 'doughnut',
		data: {
		  labels: ['Cash','Credit',],
		  datasets: [			  	
			{
				label: 'Payment Type',
				backgroundColor: ["#FFCCCC", "#CCFFCC"],
				data:[72, 28,],
			}, 
		  ]
		},
	};

	// Loads the Data into the Page
	window.onload = function() {
		var ctx = document.getElementById('chart').getContext('2d');
		window.myLine = new Chart(ctx, config);
	};
</script>	




<!--Sample Time Series Chart - Paste AFTER <script src="js/bootstrap.bundle.min.js"></script>-->
<script src="js/Chart.Time.js"></script>    
<script>
    var config = {
		type:    'line',
		data:    {
			datasets: [
				{
					label: "Degrees F",
					yAxisID: 'Degrees',
					data: [
						{x: 1729012121000, y: 24},	// UNIX Timestamp must be multiplied by 1000
						{x: 1729012221000, y: 68},	// UNIX Timestamp must be multiplied by 1000
						{x: 1729012521000, y: 58},	// UNIX Timestamp must be multiplied by 1000
					],
					fill: false,
					borderColor: "#E87500"
				},		
			]
		},	
		options: {
			scales: {
				xAxes: [{type: "time", time:{tooltipFormat: 'LLL'}, display: true, scaleLabel: {display: true, labelString: 'Time'}}],
				yAxes: [{id: 'Degrees', position: 'left', display: true, scaleLabel: {display: true,labelString: 'Degrees F'}},]
				// Parsing Time Info available in "Local Aware Formats" at https://momentjs.com/docs/#/parsing/
			}	
		}
	};

	// Loads the Data into the Page
	window.onload = function() {
		var ctx = document.getElementById('chart').getContext('2d');
		window.myLine = new Chart(ctx, config);
	};
</script>		
		



<!--Sample Bar Chart - Paste AFTER <script src="js/bootstrap.bundle.min.js"></script>-->
<script src="js/Chart.min.js"></script>	
<script>
    var config = {
        type: 'bar',
        data: {
            labels: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday',],
            datasets: [
        
                {
                    label: 'Sales',
                    backgroundColor: '#E933FF',
                    borderColor: '#CCCCCC',
                    borderWidth: 5,
                    data:[21, 84, 43, 89, 44, 73, 34,],
                },
            ]
        },
        options: {
            scales: {
                xAxes: [{stacked: false, display: true, scaleLabel: {display: true, labelString: 'Days of Week'}}],
                yAxes: [{stacked: false, display: true, scaleLabel: {display: true,labelString: 'Guest Bill (Dollars)'}}]
            }	
        }
    };

    // Loads the Data into the Page
    window.onload = function() {
        var ctx = document.getElementById('chart').getContext('2d');
        window.myLine = new Chart(ctx, config);
    };
</script>





<!--Sample Stacked Bar Chart - Paste AFTER <script src="js/bootstrap.bundle.min.js"></script>-->
<script src="js/Chart.min.js"></script>	
<script>
    var config = {
        type: 'bar',
        data: {
            labels: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday',],
            datasets: [
        
                {
                    label: 'Sales',
                    backgroundColor: '#FF0000',
                    borderColor: '#000000',
                    borderWidth: 1,
                    data:[21, 84, 43, 89, 44, 73, 34,],
                },
                {
                    label: 'Tips',
                    backgroundColor: '#00FF00',
                    borderColor: '#000000',
                    borderWidth: 1,
                    data:[10, 42, 21, 45, 22, 36, 17,],
                },                
            ]
        },
        options: {
            scales: {
                xAxes: [{stacked: true, display: true, scaleLabel: {display: true, labelString: 'Days of Week'}}],
                yAxes: [{stacked: true, display: true, scaleLabel: {display: true,labelString: 'Guest Bill (Dollars)'}}]
            }	
        }
    };

    // Loads the Data into the Page
    window.onload = function() {
        var ctx = document.getElementById('chart').getContext('2d');
        window.myLine = new Chart(ctx, config);
    };
</script>




<!--Sample Scatter Plot Chart - Paste AFTER <script src="js/bootstrap.bundle.min.js"></script>-->
<script src="js/Chart.min.js"></script>
<script>
	var config = {
		type: 'scatter',
 		data: {
       		datasets: [
       			{
					label: 'Server A',
					backgroundColor: "#45B28F",
					pointRadius: 8,
					data:[
	    				{x: 2, y: 10}, 
						{x: 6, y: 2}, 
						{x: 4, y: 7}, 
						{x: 5, y: 3}, 
					]
       			}
       		]
   		},			
		options: {
			scales: {
				xAxes: [{stacked: true, display: true, scaleLabel: {display: true, labelString: 'Bill (Dollars)'}}],
				yAxes: [{stacked: true, display: true, scaleLabel: {display: true,labelString: 'Tip (Dollars)'}}]
			}	
		}
	};

	// Loads the Data into the Page
	window.onload = function() {
		var ctx = document.getElementById('chart').getContext('2d');
		window.myLine = new Chart(ctx, config);
	};
</script>		
