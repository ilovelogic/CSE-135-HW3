const quoteArray = ["Everything excellent is as difficult as it is rare.", 
    "You could leave life right now. Let that determine what you do and say and think.",
    "Trees that are slow to grow bear the best fruit",
    "The two most important days in your life are the day you are born and the day you find out why.",
    "Some day you will be old enough to start reading fairy tales again.",
    "Yesterday is gone. Tomorrow has not yet come. We have only today. Let us begin."];
const authArray = ["Spinoza", "Marcus Aurelius", "Moliere", "Mark Twain", "C. S. Lewis", "St. Mother Teresa"];

let index = 0;

function getQuote() {
    const quote_div = document.getElementById("quote");
    quote_div.innerHTML = '\"' + quoteArray[index] + '\"';

    const auth_div = document.getElementById("author");
    auth_div.innerHTML = " - " + authArray[index];

    index = (index + 1) % 6;
}