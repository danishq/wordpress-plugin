export default function formatStringToTitleCase(input) {
  if (!input || typeof input !== "string") return "";

  // Replace underscores or camelCase with spaces and split into words
  const words = input
    .replace(/([a-z])([A-Z])/g, "$1 $2") // Convert camelCase to space-separated
    .replace(/_/g, " ") // Replace underscores with spaces
    .split(" ");

  // Capitalize the first letter of each word and make the rest lowercase
  return words
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
    .join(" ");
}
