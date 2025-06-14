// const backendURL = `https://api.moogle.app/api`;
const backendURL = `http://127.0.0.1:8000/api`;

document.addEventListener("DOMContentLoaded", () => {
  const searchButton = document.getElementById("search-button");
  const cringeButton = document.getElementById("cringe-button");
  const searchBar = document.getElementById("search-bar");
  let infoContainer;
  let formattedInfo;
  let html;

  if (searchButton) {
    searchButton.addEventListener("click", () => {
      const query = searchBar.value.trim();
      if (query) {
        search(query);
      } else {
        alert("Please enter a search query");
      }
    });
  }

  if (cringeButton) {
    cringeButton.addEventListener("click", () => {
      cringe();
    });
  }

  if (searchBar) {
    searchBar.value = "";
    searchBar.placeholder = "Search for something!";
    searchBar.addEventListener("keydown", (event) => {
      if (event.key == "Enter") {
        const query = searchBar.value.trim();
        if (query) {
          search(query);
        } else {
          alert("Please enter a search query");
        }
      }
    });
  }

  console.log(`Pinging backend at ${backendURL}...`);
  // Check if backend is running and fetch number of entries from the DB
  fetch(`${backendURL}/stats`)
    .then((res) => res.json())
    .then((data) => {
      if (data.status === "up") {
        infoContainer = document.getElementById("info-container");

        formattedInfo = getFormattedInfoString(data.pages);

        html = `<span class="info">${formattedInfo}</span>`;
        infoContainer.innerHTML = html;
      } else if (data.status === "down") {
        serverDown();
      }
    })
    .catch((error) => {
      console.error("Error fetching data:", error);
      serverDown();
    });

  function serverDown() {
    infoContainer = document.getElementById("info-container");
    infoContainer.innerHTML = `Server down - please come back later`;
    searchBar.disabled = true;
    searchBar.placeholder = "hello";
    searchBar.value = "Please come back later...";
    searchButton.disabled = true;
    cringeButton.disabled = true;
  }
});

function getFormattedInfoString(pageCount) {
  const formatter = new Intl.NumberFormat("en", { notation: "compact" });

  let formattedString = "Contains ";
  const approxCount = formatter.format(pageCount);

  let order = approxCount.slice(-1);
  switch (order) {
    case "K":
      formattedString += `~ ${approxCount.slice(0, -1)} thousand`;
      break;
    case "M":
      formattedString += `~ ${approxCount.slice(0, -1)} million`;
      break;
    case "B":
      formattedString += `~ ${approxCount.slice(0, -1)} billion`;
      break;
    default:
      formattedString += `${approxCount}`;
      break;
  }

  formattedString += " results (soon to be much bigger)";

  return formattedString;
}

async function search(query) {
  try {
    const encodedQuery = encodeURIComponent(query);
    const requestUrl = `${backendURL}/search?q=${encodedQuery}`;
    console.log(requestUrl);

    window.location.href = requestUrl;
  } catch (error) {
    console.log(error.message);
  }
}

async function cringe() {
  try {
    const cringeUrl = `${backendURL}/cringe`;
    // const cringeUrl = `http://localhost:8000/api/cringe`;
    window.location.href = cringeUrl;
  } catch (error) {
    // console.log(error.message);
  }
}
