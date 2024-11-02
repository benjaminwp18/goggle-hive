/* REQUIRES JQUERY */

var overlay = $("#overlay")[0];

// Upload popup elements
var uploadPopup = $("#uploadPopup")[0];
var fileInput = $("#fileInput")[0];
var uploadResponse = $("#uploadResponse")[0];
var uploadProgress = $("#uploadProgress")[0];
var locationInput = $("#location")[0];

// Rename popup elements
var renameButton = $("#renameButton")[0];
var renamePopup = $("#renamePopup")[0];
var renameInput = $("#renameInput")[0];
var renameResponse = $("#renameResponse")[0];
var renameTitle = $("#renameTitle")[0];
var renameId = $("#renameId")[0];
var renameType = $("#renameType")[0];
var renameSubmit = $("#renameSubmit")[0];

// Download popup elements
var downloadIframe = $("#downloadIframe")[0];

// Tool buttons
var newItemButton = $("#newItemButton")[0];
var selectMoveButton = $("#selectMoveButton")[0];
var deleteButton = $("#deleteButton")[0];
var downloadButton = $("#downloadButton")[0];

var doMoveButton = $("#doMoveButton")[0];
var moveType = $("#moveType")[0];

// Search Bar
var toggleSearchButton = $("#toggleSearchButton")[0];
var searchInput = $("#searchInput")[0];
var searchBar = $("#searchBar")[0];
var searchBarIsShown = false;

var fileCont = $("#fileContainer")[0];

var op = 0;

var queryStr = window.location.search;
var urlPar = new URLSearchParams(queryStr);

window.onload = function() {
	checkButtons();
	getFiles();
}

function getFiles(callback) {
	if (searchBarIsShown) {
		fileCont.innerHTML = "<p>Enter a search query to view results here.</p>";
		return;
	}
	
	var data = {authToken: authToken};
	if (urlPar.has("classId")) data.classId = urlPar.get("classId");
	else window.location.replace("my_classes.php");
	
	if (urlPar.has("projectId")) data.projectId = urlPar.get("projectId");
	if (urlPar.has("folderId")) data.folderId = urlPar.get("folderId");
	
	$.ajax({
		dataType: "json",
		url: "includes/get_files.pst.php",
		type: "POST",
		data: data,
		success: function(data) {
			if (data.redirect) window.location.replace(data.redirect);
			else fileCont.innerHTML = data.content;
		},
		error: function(jqXHR, textStatus, errorThrown) {
			fileCont.innerHTML = "<p>An error occurred. Please try again.</p>";
		},
		complete: callback ? (jqXHR, textStatus) => callback(jqXHR, textStatus) : () => {}
	});
}

function uploadFiles(e) {
	e.preventDefault();
	$.ajax({
		xhr: function() {
			var xhr = new window.XMLHttpRequest();
			xhr.upload.addEventListener("progress", function(e) {
				if (e.lengthComputable) {
					uploadProgress.value = Math.round(e.loaded / e.total * 100);
				}
			}, false);
			
			return xhr;
		},
		contentType: false,
		processData: false,
		dataType: "json",
		url: "includes/upload.pst.php",
		type: "POST",
		data: new FormData(uploadPopup),
		success: function(data) {
			if (data.redirect) window.location.replace(data.redirect);
			else {
				uploadResponse.innerHTML = data.content;
				getFiles();
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			uploadResponse.className = "error";
			uploadResponse.innerHTML = "An error occurred. Please try again.";
		}
	});
}

function doRename(e) {
	e.preventDefault();
	if (renameType.value == "normal") {
		var data = {authToken: authToken, name: renameInput.value, type: urlPar.has("projectId") ? "file" : "project", itemId: renameId.value};
		$.ajax({
			dataType: "json",
			url: "includes/rename.pst.php",
			type: "POST",
			data: data,
			success: function(data) {
				if (data.redirect) window.location.replace(data.redirect);
				else {
					renameResponse.classList.toggle("error", data.type == "error");
					renameResponse.classList.toggle("success", data.type == "success");
					renameResponse.innerHTML = data.content;
					
					if (data.type == "success") {
						closeRenamePopup();
						getFiles((jqXHR, status) => {
							if (status == "success") {
								$("#file" + renameId.value)[0].scrollIntoView({behavior: "smooth"});
								select({}, renameId.value);
							}
							else alert("Error retrieving files.");
						});
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				renameResponse.innerHTML = "An error occurred. Please try again.";
			}
		});
	}
	else if (renameType.value == "folder" || renameType.value == "project") {
		var data = {authToken: authToken, name: renameInput.value, type: renameType.value};
		if (urlPar.has("classId")) data.classId = urlPar.get("classId");
		else window.location.replace("my_classes.php");
		
		if (urlPar.has("projectId")) data.projectId = urlPar.get("projectId");
		if (urlPar.has("folderId")) data.folderId = urlPar.get("folderId");
		
		$.ajax({
			dataType: "json",
			url: "includes/new_item.pst.php",
			type: "POST",
			data: data,
			success: function(data) {
				if (data.redirect) window.location.replace(data.redirect);
				else {
					renameResponse.classList.toggle("error", !data.success);
					renameResponse.classList.toggle("success", data.success);
					renameResponse.innerHTML = data.content;
					
					if (data.success) {
						closeRenamePopup();
						getFiles((jqXHR, status) => {
							if (status == "success") {
								$("#file" + data.id)[0].scrollIntoView({behavior: "smooth"});
								select({}, data.id);
							}
							else alert("Error retrieving files.");
						});
					}
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				renameResponse.innerHTML = "An error occurred. Please try again.";
			}
		});
	}
	else {
		renameResponse.innerHTML = "Invalid rename.";
	}
}

function selectMove() {
	var panels = document.getElementsByClassName("selected");
	var items = "";
	for (let i = 0; i < panels.length; i++) {
		items += panels[i].id.substring(4) + ",";
	}
	items = items.substring(0, items.length - 1);

	if (urlPar.has("folderId")) var type = "childFiles";
	else if (urlPar.has("projectId")) var type = "parentFiles";
	else if (urlPar.has("classId")) var type = "projects";
	else location.replace("my_classes.php");
	
	$.ajax({
		dataType: "json",
		url: "includes/select_move.pst.php",
		type: "POST",
		data: {authToken: authToken, items: items, type: type},
		success: function(data) {
			if (data.redirect) location.replace(data.redirect);
			else if (data.success == true) location.reload();
			else {
				alert("Move failed. Please try again.");
				location.reload();
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert("Move failed. Please try again.");
			location.reload();
		}
	});
}

function cancelMove() {
	$.ajax({
		dataType: "html",
		url: "includes/cancel_move.pst.php",
		type: "POST",
		data: {authToken: authToken},
		complete: function() {
			location.reload();
		}
	});
}

function doMove() {
	var data = {authToken: authToken};
	if (urlPar.has("classId")) data.classId = urlPar.get("classId");
	else window.location.replace("my_classes.php");
	
	if (urlPar.has("projectId")) data.projectId = urlPar.get("projectId");
	if (urlPar.has("folderId")) data.folderId = urlPar.get("folderId");
	
	$.ajax({
		dataType: "json",
		url: "includes/do_move.pst.php",
		type: "POST",
		data: data,
		success: function(data) {
			if (data.redirect) window.location.replace(data.redirect);
			else if (data.success) {
				getFiles((jqXHR, status) => {
					if (status == "success") alert("Files successfully moved.");
					else alert("Error retrieving files.");
					location.reload();
				});
			}
			else {
				if (data.message == "1") alert("File move failed. Please try again.");
				else if (data.message == "2") alert("File move failed. Please try again.");
				else if (data.message == "3") alert("File move failed. This account does not have access to the requested class.");
				else if (data.message == "4") alert("File move failed. This account does not have access to the requested project, or the requested project does not exist.");
				else if (data.message == "5") alert("File move failed. This account does not have access to the requested folder, or the requested folder does not exist.");
				else if (data.message == "6") alert("File move failed. Invalid folder ID. Please try again.");
				else if (data.message == "7") alert("File move failed. One or more of the selected files do not exist. Please try again.");
				else if (data.message == "8") alert("File move failed. Invalid project ID. Please try again.");
				else if (data.message == "9") alert("File move failed. Please try again.");
				else if (data.message == "10") alert("File move failed. Please try again.");
				location.reload();
			}
		},
		error: function(jqXHR, textStatus, errorThrown) {
			alert("Move failed. Please try again.");
			location.reload();
		}
	});
}

function deleteFiles() {
	var panels = document.getElementsByClassName("selected");
	var items = "";
	for (let i = 0; i < panels.length; i++) {
		items += panels[i].id.substring(4) + ",";
	}
	items = items.substring(0, items.length - 1);

	// What are we deleting?
	if (urlPar.has("projectId")) var type = "files";
	else if (urlPar.has("classId")) var type = "projects";
	else location.replace("my_classes.php");
	
	if (confirm("Are you sure you want to delete " + items.split(',').length + " item" + (items.split(',').length > 1 ? "s" : "") + "?")) {
		$.ajax({
			dataType: "json",
			url: "includes/delete_files.pst.php",
			type: "POST",
			data: {authToken: authToken, items: items, type: type},
			success: function(data) {
				if (data.redirect) location.replace(data.redirect);
				else if (data.success == true) location.reload();
				else {
					alert("Delete action failed. Please try again.");
					location.reload();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("Delete action failed. Please try again.");
				location.reload();
			}
		});
	}
}

function downloadFiles() {
	var panels = document.getElementsByClassName("selected");
	var items = "";
	for (let i = 0; i < panels.length; i++) {
		items += panels[i].id.substring(4) + ",";
	}
	items = items.substring(0, items.length - 1);

	// What are we downloading?
	if (urlPar.has("projectId")) var type = "files";
	else if (urlPar.has("classId")) var type = "projects";
	else location.replace("my_classes.php");
	
	if (confirm("Are you sure you want to download " + items.split(',').length + " item" + (items.split(',').length > 1 ? "s" : "") + "?")) {
		// Update $_SESSION with files to download
		$.ajax({
			dataType: "json",
			url: "includes/prepare_download.pst.php",
			type: "POST",
			data: {authToken: authToken, items: items, type: type},
			success: function(data) {
				if (data.redirect) location.replace(data.redirect);
				else if (data.success == true) {  // Run download
					downloadIframe.src = "includes/download_files.pst.php";
					showDownloadPopup();
				}
				else {
					alert("Download action failed. Please try again.");
					location.reload();
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert("Download action failed. Please try again.");
				location.reload();
			}
		});
	}
}

function toggleSearchBar() {  // Toggle search bar visibility
	if (searchBarIsShown) {
		searchBar.style.display = "none";
		toggleSearchButton.classList.remove("inUse");
	}
	else {
		searchBar.style.display = "block";
		toggleSearchButton.classList.add("inUse");
		searchInput.focus();
	}
	
	searchBarIsShown = !searchBarIsShown;
	getFiles();
}

function searchFiles(searchProjects) {  // Basically just getFiles() but also passing searchInput.value along as "searchTerm" in the data
	var data = {authToken: authToken, searchTerm: searchInput.value};
	
	if (urlPar.has("classId")) data.classId = urlPar.get("classId");
	else window.location.replace("my_classes.php");
	if (urlPar.has("projectId")) data.projectId = urlPar.get("projectId");
	if (urlPar.has("folderId")) data.folderId = urlPar.get("folderId");
	if (!urlPar.has("folderId") && !urlPar.has("projectId")) data.returnOnly = (searchProjects ? 'projects' : 'files');
	
	$.ajax({
		dataType: "json",
		url: "includes/get_files.pst.php",
		type: "POST",
		data: data,
		success: function(data) {
			if (data.redirect) window.location.replace(data.redirect);
			else fileCont.innerHTML = data.content;
		},
		error: function(jqXHR, textStatus, errorThrown) {
			fileCont.innerHTML = "<p>An error occurred. Please try again.</p>";
		}
	});
}

function tryKeyedSearch(e) {
	if (e.key == 'Enter') searchFiles(false);
}

function deselectAll() {
	var files = document.getElementsByClassName("filePanel");
	for (let i = 0; i < files.length; i++) files[i].classList.remove("selected", "latest");
}

function checkButtons() {
	if (renameButton) {
		renameButton.disabled = downloadButton.disabled = deleteButton.disabled = selectMoveButton.disabled = document.getElementsByClassName("selected latest").length == 0;
		newItemButton.innerHTML = "New&nbsp;" + (urlPar.has("projectId") ? "&nbsp;Folder" : "Project");
	}
	else if (doMoveButton) {
		if ((moveType.value.includes("Files") && !urlPar.has("projectId")) || (moveType.value == "projects" && urlPar.has("projectId"))) doMoveButton.disabled = true;
		else doMoveButton.disabled = false;
	}
}

function select(e, id) {
	if (e.shiftKey) {
		var files = document.getElementsByClassName("filePanel");
		var start = Number(document.getElementsByClassName("latest").length == 0 ? 0 : document.getElementsByClassName("latest")[0].getAttribute("data-position"));
		var end = Number($("#file" + id)[0].getAttribute("data-position"));
		for (let i = (start >= end ? end : start); i < (start >= end ? start : end); i++) {
			files[i].classList.add("selected");
		}
		files[start].classList.remove("latest");
		$("#file" + id)[0].classList.add("selected", "latest");
	}
	else if (e.ctrlKey || e.metaKey) {
		if ($("#file" + id)[0].classList.contains("selected")) {
			$("#file" + id)[0].classList.remove("selected", "latest");
		}
		else {
			if ($(".latest")[0].length > 0) document.getElementsByClassName("latest")[0].classList.remove("latest");
			$("#file" + id)[0].classList.add("selected", "latest");
		}
	}
	else {
		deselectAll();
		$("#file" + id)[0].classList.add("selected", "latest");
	}
	
	return false;
}

function newItem() {
	checkButtons();
	showRenamePopup("New " + (urlPar.has("projectId") ? "Folder" : "Project"));
}

document.body.addEventListener("click", function(e) { // deselectAll() if clicking on non-panel (non-file) element
	e = e || window.event;
	var target = e.target || e.srcElement;
	if (!target.classList.contains("filePanel") && !target.parentElement.classList.contains("filePanel") && target.tagName != "BUTTON" && target.tagName != "INPUT" && target.parentElement.tagName != "FORM" && target.tagName != "FORM") deselectAll();
	checkButtons();
}, false);

function showRenamePopup(sel) {
	if (!sel) {
		let f = document.getElementsByClassName("selected latest")[0];
		renameTitle.innerHTML = "Rename " + f.children[1].innerText;
		renameId.value = f.id.substring(4);
		renameType.value = "normal";
		renameInput.placeholder = "Enter New Name";
		renameSubmit.value = "Rename";
	}
	else {
		renameType.value = urlPar.has("projectId") ? "folder" : "project";
		renameTitle.innerHTML = "Create " + sel;
		renameInput.placeholder = "Enter Name";
		renameSubmit.value = "Create";
	}
	
	renamePopup.style.display = "inline-block";
	overlay.style.display = "inline-block";
	renameInput.focus();
	fadePopup(renamePopup, 0.15, 10, "inline-block");
}

function closeRenamePopup() {
	fadePopup(renamePopup, -0.15, 10, "none");
	setTimeout(function() {
		renameResponse.innerHTML = "";
		renameInput.value = "";
	}, 1 / 0.15 * 11);
}

function showUploadPopup() {
	if (locationInput.value == "notAllowed" || locationInput.name == "notAllowed") window.location.replace("my_classes.php");
	uploadPopup.style.display = "inline-block";
	overlay.style.display = "inline-block";
	fadePopup(uploadPopup, 0.15, 10, "inline-block");
}

function hideUploadPopup() {
	fadePopup(uploadPopup, -0.15, 10, "none");
	setTimeout(function() {
		uploadProgress.value = 0;
		uploadResponse.innerHTML = "";
		fileInput.value = "";
	}, 1 / 0.15 * 11);
}

function showDownloadPopup() {
	downloadPopup.style.display = "inline-block";
	overlay.style.display = "inline-block";
	fadePopup(downloadPopup, 0.15, 10, "inline-block");
}

function hideDownloadPopup() {
	fadePopup(downloadPopup, -0.15, 10, "none");
	setTimeout(function() {
		downloadIframe.src = "";
	}, 1 / 0.15 * 11);
}

function fadePopup(popup, tick, ms, endState) {
	let fader = setInterval(function() {
		op = Math.round(100 * (op + tick)) / 100;
		popup.style.opacity = op;
		overlay.style.opacity = op;
		if (op >= 1 || op <= 0) {
			clearInterval(fader);
			popup.style.display = endState;
			overlay.style.display = endState;
		}
	}, ms);
}