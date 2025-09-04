# CSE-135-HW2
Repo for the CSE 135 third homework web server, available at [annekelley.site](https://annekelley.site). The server IP address is 143.198.147.148

## Notes on Setup
As I was getting this up and running, I used a Google doc for tracking details to use in debugging down the road. [Google Doc Notes](https://docs.google.com/document/d/1myGtFbDzZ5-MzCQncl51wMC3QlLp-PAuKZ_tOACnpPk/edit?usp=sharing)

## Progressive Enhancement
As firm believers in progressive enhancement, we rely on log files, then client hints, and lastly Javascript for our data collection.

## Data Ingest
We perform an immediate write to short term storage and carry out sanitization and
processing later on.

## Dashboard
We offer an explanation of all our design decisions, including the chart types representing our data, the metrics we worked with, amd so on. 

### Browser Stack Bar Chart Descision
We wanted to understand our browser distribution so we could focus on optimizing our code for the browsers interacting with our webste the most. We originally considered a pie chart, since it would represent the ratios of different browsers among our users. In the end, we opted for a bar chart for the following reasons:
 - We have a wide variety of different browser types to compare, and when the differences in area may be small, it is best to use bars. After all, differences in rectangular area are more easily discernible than differences in the area of circles.
 - Given that browser types can have multiple versions, we planned to group the browser types together and indicate the versions within each grouping. A bar chart lends itself nicely to grouping versions using stacks, whereas a pie chart with stacks in the slices looks overly complex. 

 

## Report
Provide a written explanation of your design decisions (which metric you decided to report on, which chart types you chose for which data, what metrics you decided to display and why, etc). Be thorough in your explanation to demonstrate to the teaching team that you explored your options and made your decisions based on legitimate reasoning and user centered thinking.