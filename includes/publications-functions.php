<?php
// formats the publication types
function pub_type($number) {
    switch ($number) {
        case 1:
            return "Books";
        case 2:
            return "Book Chapters";
        case 3:
            return "Journal Papers (refereed)";
        case 4:
            return "Conference Proceedings";
        case 5:
            return "Other Unreferenced Publications";
        case 7:
            return "Patents";
        case 16:
            return "Pending Patents";
        case 17:
            return "Disclosures";
        case 8:
            return "Theses or Dissertations";
        case 15:
            return "News Coverage";
        case 6:
            return "Presentations";
        case 9:
            return "Invited Presentations";
        case 10:
            return "Plenary Presentations";
        case 11:
            return "Tutorials";
        case 12:
            return "Posters";
        case 13:
            return "Workshops";
        case 14:
            return "Seminar";
        default:
            return "Invalid Type";
    }
}