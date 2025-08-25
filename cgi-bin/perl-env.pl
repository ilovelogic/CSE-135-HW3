#!/usr/bin/perl
print "Cache-Control: no-cache\n";
print "Content-type: text/html \n\n";

# print HTML file top
print <<END;
<!DOCTYPE html>
<html><head><title>Environment Variables</title>
</head><body><h1 align="center">Environment Variables</h1>
<hr>
END

print "<h1>Anne was here</h1>";
# Loop over the environment variables and print each variable and its value
foreach $variable (sort keys %ENV) {
  print "<b>$variable:</b> $ENV{$variable}<br />\n";
}

# Print the HTML file bottom
print "</body></html>";
