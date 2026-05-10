function formatDate(dateString, includeTime = false, note = false) {
  // Parse the date string
  if (!dateString || isNaN(new Date(dateString).getTime())) {
    return "-";
  }

  const date = new Date(dateString);

  // Define an array of month names
  const monthNames = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sept",
    "Oct",
    "Nov",
    "Dec",
  ];

  // Extract the day, month, and year
  const day = date.getDate();
  const month = monthNames[date.getMonth()];
  const year = date.getFullYear();

  // Format the date part
  let formattedDate = `${month} ${day}, ${year}`;

  // Check if the time should be included
  if (includeTime || note) {
    // Extract hours and minutes
    let hours = date.getHours();
    const minutes = date.getMinutes();

    // Convert hours to 12-hour format and determine AM/PM
    const ampm = hours >= 12 ? "PM" : "AM";
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'

    // Format minutes to always have two digits
    const formattedMinutes = minutes < 10 ? "0" + minutes : minutes;

    // Add the time to the formatted date
    const time = `${hours}:${formattedMinutes} ${ampm}`;
    formattedDate = note
      ? `${formattedDate} at ${time}` // Format for note
      : `${formattedDate} ${time}`; // Default format
  }

  return formattedDate;
}

// Example usage
export default formatDate;
