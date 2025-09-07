# CSE-135-HW2
Repo for the CSE 135 third homework web server, available at [annekelley.site](https://annekelley.site). The server IP address is 143.198.147.148

## Notes on Setup
As we were getting this up and running, we used a Google doc for tracking details to use in debugging down the road. [Google Doc Notes](https://docs.google.com/document/d/1myGtFbDzZ5-MzCQncl51wMC3QlLp-PAuKZ_tOACnpPk/edit?usp=sharing)

## Progressive Enhancement
As firm believers in progressive enhancement, we rely on log files first and use Javascript to enhance our data collection. To maintain a SSoT (Single Source of Truth), we automatically run a script every hour on our server that injests our log files into the apacheLogs table of our database.


## Dashboard
We offer an explanation of all our design decisions, including the chart types representing our data, the metrics we worked with, and so on. 

### User Language Metric
During a lecture, Professor Powell noted how some sites will often route a user to pages in their native language based on their userLang header. We wondered if it would be worthwhile to prepare different language versions of our pages and set up such a routing for our own site. 


 

## Report
Provide a written explanation of your design decisions (which metric you decided to report on, which chart types you chose for which data, what metrics you decided to display and why, etc). Be thorough in your explanation to demonstrate to the teaching team that you explored your options and made your decisions based on legitimate reasoning and user centered thinking.

### User Language Metric
During a lecture, Professor Powell noted how some sites will often route a user to pages in their native language based on their userLang header. We wondered if it would be worthwhile to prepare different language versions of our pages and set up such a routing for our own site. 

### User Language Pie Chart
In our report about designing versions of our pages for different languages, we needed to understand which language groups were most present among our users. 
 - Chart choice: The numbers themselves are not as important as how the categories are in ratio to one another, so we opted for a pie chart. 
 - Navigation decision: We went with the format of a ZingChart navpie to keep the user from being distracted by the tiny fraction of our pie taken up by 3 languages. These 3 languages made up a total of 6.3% of the pie, so we chose to group them under an "Others" slice, yet still make their details available for those interested. The navpie makes this easy, grouping all slices less than 15% together, and making those slices and their details accessible through clicking on the Others slice.
  - Coloring selection: Spanish is the only language, other than English, that took up a notable portion of the pie. As a result, we decided to argue for creating Spanish versions of relevant web pages. To help the reader pay the most attention to the Spanish slice, we colored the English slice green and the Others slice blue. As a result, the red Spanish slice pops out to the reader, as intended.

### Pages Visited by Spanish Speakers Grid
To argue for which pages should have a Spanish version, we planned to show the files the Spanish speakers accessed and the total number of visits per file. Showing the page names and visit numbers in a grid, without a visual, offered a simple format that gave the impression of being more autthoritative, because of the pure numbers.

### Browser Type Metric
We discussed in class the importance of user experience and touched on the fact that different browsers need different support. As a result, we want to know if our site is meeting the needs of our user's differing browsers or not, so we turn to our browser distribution and errors.

### Error Occurences Grid
We began our report with looking at the error messages themselves, along with the count of each error. We led with the errors to give our reader motivation for why need to look into our browser demographics. We went with a grid to display the information since the error messages were too long to format nicely into a visual. 

### Browser Stack Bar Chart
We next bring the browser type distribution into the picture to make sense of the aforementioned error data. We originally considered using a pie chart, since it would represent the ratios of different browsers among our users. In the end, we opted for a bar chart for the following reasons:
 - We have several different browser types to compare, and when the differences in area may be small (such as between Safari and Firefox), it is best to use bars. After all, differences in rectangular area are more easily discernible than differences in the area of circles.
 - Given that browser types can have multiple subcategories, we planned to group the browser types and indicate the operating systems within each grouping. A bar chart lends itself nicely to noting operating systems using stacks, whereas a pie chart with stacks in the slices looks overly complex. 
