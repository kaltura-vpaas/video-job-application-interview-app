populateJobPositionsSelect = function () {
  const getComeetJobsListUrl =
    "https://www.comeet.co/careers-api/2.0/company/E2.00D/positions?token=2EDEA12ED017688C7147B1A555DA2ED&details=false";
  fetch(getComeetJobsListUrl, {
    method: "GET",
  })
    .then(function (response) {
      // The API call was successful!
      if (response.ok) {
        return response.json();
      } else {
        return Promise.reject(response);
      }
    })
    .then(function (data) {
      // This is the JSON from our response
      populateJobPositionsJson(data);
    })
    .catch(function (err) {
      // There was an error
      console.log("Something went wrong.", err);
    });

  var populateJobPositionsJson = function (data) {
    //console.log(data);
    var dropdown = document.getElementById("jobpos");

    var jobs = [];
    for (var i = 0; i < data.length; i++) {
      if (jobs[data[i].department] == undefined) jobs[data[i].department] = [];
      var option = document.createElement("option");
      option.text = data[i].name;
      option.value = data[i].uid;
      jobs[data[i].department].push(option);
    }
    for (const [key, depJobs] of Object.entries(jobs)) {
      var optGroup = document.createElement("optgroup");
      optGroup.label = key;
      dropdown.appendChild(optGroup);
      for (var i = 0; i < depJobs.length; i++) {
        optGroup.appendChild(depJobs[i]);
      }
    }

    new SlimSelect({
      select: "#jobpos",
    });
  };
};
