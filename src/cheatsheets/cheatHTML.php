<html>
	<head>	<!-- The HEAD starts here. It won't be visible on the webpage but can help set up various background parts of the page -->
		<title>Sample HTML Page</title>							<!-- This title is what will show up on the tab of the browser -->
		<link href="css/someStyle.css" rel="stylesheet">		<!-- Sometimes we use CSS to make things appear a certain way on a webpage. We'll get into that later. -->
	</head>

	<body class="text-center">									<!-- The body is the start of the visible part of the webpage. -->
		<div id="welcome">										<!-- This is a page divider. We can give it an "id" to help us remember what's going on in this section -->				
			<h1>BIG Heading Title</h1>																							<!-- The <h1> tag lets us make big headers -->		
			<h2>This one isn't as big, but that's okay.</h2>																	<!-- <h2>, <h3>, etc. tags are also headers, but get smaller as you go up -->	
			<p>Check out these nifty paragraphs of text. You could literally type just about anything you wanted to here.</p>	<!-- The <p> tag lets us make paragraphs of text -->	
			<p>No, seriously! Give it a try. Isn't learning how to write HTML the best thing that's ever happened to you?</p>
		</div>													<!-- This is the end of our "welcome" divider. Doesn't that look nice and organized? -->	
		
		<div id="info">
			<p>
				<b>This is some bold text!</b>					<!-- Check out the <b> tag to make things appear bold -->
				<br>											<!-- The <br> tag creates a line break between items. You can use multiple breaks in a row to space things apart -->
				<i>This is some italics text!</i>				<!-- The <i> tag italicizes text -->
			</p>
			<p>
				<a href="http://www.google.com">Click this link to go to Google</a><br>			<!-- Here's a link tag to an external website -->
				<a href="sampleHTML2.php">Click this link to another page on my site</a><br>		<!-- Here's a link tag to an internal page on your own website -->
				<img src="cat1.jpeg" width="400px" height="300px"><br>							<!-- You can embed images on your page and control the height/width if you want-->
				<img src="images/cat2.jpeg"><br>												<!-- Here's an image that in the otherImages folder on your website -->
			</p>
		</div>
		<div id="data">
			<p>
				Here's a list of why I love lists:	
				<ul>																			<!-- The <ul> tag creates an unordered list of items -->
					<li>They're really easy to read</li>										<!-- The <li> tag creates list item on that list -->
					<li>They're a great way to organize information</li>
					<li>They're a lot of fun!</li>
				</ul>
			</p>
			<p>
				Here's an ORDERED list of why I love lists:
				<ol>																			<!-- The <ul> tag creates an ordered list of items -->
					<li>They're even easier to read</li>
					<li>They're an even greater way to organize information</li>
					<li>They're even more fun!</li>
				</ol>
			</p>
			<p>
				How about an example of tables? Those are fun, right?
				<table>																			<!-- The <table> tag starts a table -->
					<tr>																		<!-- The <tr> tag creates a new row of data -->
						<th>First Name</th>														<!-- The <th> tag creates header item in the table -->
						<th>Last Name</th>
						<th>Occupation</th>
					</tr>																		<!-- The </tr> tag ends the row of the table -->
					<tr>																		<!-- This <tr> starts a new row of data on the table -->
						<td>Tony</td>															<!-- The <td> tag creates a field of data on the table -->
						<td>Stark</td>
						<td>Entrepreneur</td>
					</tr>
					<tr>
						<td>Steve</td>
						<td>Rogers</td>
						<td>Soldier</td>
					</tr>
					<tr>
						<td>Bruce</td>
						<td>Banner</td>
						<td>Scientist</td>
					</tr>
				</table>																		<!-- Look at how nicely all these tags are nested and appropriately closed -->
			</p>
		</div>
	</body>
</html>
