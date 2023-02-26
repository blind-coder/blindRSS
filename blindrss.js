var globalSpecialFeedsTree;
var globalEntriesTree;
var globalEntriesTreeData;
var labelNames = new Array("label-success", "label-warning", "label-info");

var curFeed = false;

var cronjobTimer = setInterval(cronjob, 60000); // every minute
var cronjobData = {lastRun: new Date()};

var Feeds = new Object();
Feeds.feeds = new Object();
Feeds.root = false;
Feeds.tree = false;

Feeds.add = function(data){{{
	var newFeed = new Feed(data);
	this.feeds[data.ID] = newFeed;
	if (this.root == false || this.root.startID > newFeed.startID){
		/* We can't assume that the root feed has the ID 1.
		 * Backwards compatibility means we might encounter
		 * an installation which didn't use a root directory.
		 */
		this.root = newFeed;
	}
}}}
Feeds.get_by_id = function(id){{{
	for (var i = 0; i < this.feeds.length; i++){
		if (this.feeds[i].ID == id){
			return this.feeds[i].ID;
		}
	}
	return null;
}}}
Feeds.get = function (){{{
	Feeds.feeds = new Object();
	Feeds.root = false;
	var that = this;
	$.ajax({
		url: "rest.php/feeds",
		type: "GET",
		dataType: "json",
		success: function(data){
			if (!data){
				return;
			}
			$.each(data, function(k,v){
				that.add(v);
			});

			Feeds.show();
			that.root.updateCount();
			that.initCollapsedState();
		}
	});
}}}
Feeds.initCollapsedState = function(){{{
	var that = this;
	$.each(that.feeds, function(k,v){
		if (v.data.collapsed == "yes"){
			var node = that.tree.tree('getNodeById', v.data.ID);
			that.tree.tree('closeNode', node, false);
		}
	});
}}}
Feeds.show = function (){{{
	var that = this;
	if (that.tree){
		that.tree.tree("destroy");
		that.tree = false;
	}
	var dBody = $("#feeds");
	dBody.tree(false);
	dBody.empty();

	var tree = [
		{ id: "specialFeeds",
			label: "Special Feeds",
			num: 0,
			movable: false,
			children: [
				{ id: "specialUnread",
					label: "Unread entries",
					num: 0,
					movable: false,
					action: function(event){ getEntries("rest.php/unread", "spinFeed_specialUnread"); }
				},
				{ id: "specialFavorites",
					label: "Favorites",
					num: 0,
					movable: false,
					action: function(event){ getEntries("rest.php/favorites", "spinFeed_specialFavorites"); }
				},
				{ id: "specialTags",
					label: "Tags",
					num: 0,
					movable: false,
					children: []
				}
			]
		}
	];

	tree = tree.concat(that.root.jqTree());

	var t = dBody.tree({
		data: tree,
		autoOpen: true,
		dragAndDrop: true,
		selectable: true,
		useContextMenu: false,
		onCanMove: function(node){{{ /* not all feeds can be moved */
			return node.movable;
		}}},
		onCanMoveTo: function(moved_node, target_node, position) {{{
			/* Can only move inside directories
			 * Can not move outside Root feed
			 * Can not move inside special feeds
			 */
			if (position == "inside"){
				return target_node.feed.isDirectory;
			}
			if (!target_node.parent.parent){
				return false;
			}
			if (target_node.id.toString().match(/^special/)){
				return false;
			}
			return true;
		}}},
		onCreateLi: function(node, $li){{{
			var that = node.feed; // passed from .jqTree()

			if (!that){ /* is this a special feed? */
				var utils = $("<div>").addClass("utils");
				utils.append(
					$("<span>").addClass("floatLeft spin")
					           .attr("id", "spinFeed_"+node.id)
										 .append("&nbsp;")
				);
				utils.append(
					$("<i>").addClass("icon-pencil floatRight")
					        .css("visibility", "hidden") // small hack to adjust right margin
				);
				utils.append(
					$("<span>").addClass("floatRight badge")
					           .attr("id", "numNew_"+node.id)
										 .append(node.num > 0 ? node.num : "")
				);
				$li.find(".jqtree-title").append(utils);
				return;
			}

			that.spin = $("<span>").addClass("floatLeft spin")
				                     .attr("id", "spinFeed_"+that.data.startID)
														 .append("&nbsp;");
			that.nameFeed = $li.find(".jqtree-title");

			that.buttons = new Object();
			/* This button will only be visible when the mouse hovers over the feed/group to prevent cluttering the UI */
			that.buttons.settings = $("<i class='icon-pencil floatRight editButton' />")
				.on("click", function(){
					that.showSettings();
					return false; // last 'click' handler
				});
			that.buttons.newMessage = $("<span id='numNew_"+that.data.startID+"' class='badge floatRight ' />")
				.on("click", function() {
					that.markAllRead();
					return false; // last 'click' handler
				});

			if (!that.isDirectory){
				if (that.data.favicon == "" || that.data.favicon == undefined){
					that.nameFeed.prepend("<i class='icon-rss favicon'> </i>");
				} else {
					that.nameFeed.prepend("<img src='"+that.data.favicon+"' class='favicon' /> ");
				}
			}

			that.nameFeed
				.append($("<span class='utils' />")
					.append(that.spin)
					.append(that.buttons.settings)
					.append(that.buttons.newMessage)
				);
		}}}
	});

	t.bind(
		'tree.open',
		function(event){{{
			var that = event.node.feed;
			if (that)
				that.collapse(false);
		}}}
	);
	t.bind(
		'tree.close',
		function(event){{{
			var that = event.node.feed;
			if (that)
				that.collapse(true);
		}}}
	);

	t.bind(
		'tree.click',
		function(event){{{
			if (!event.node){
				return;
			}
			if (typeof event.node.action == "function"){
				event.node.action(event);
				return;
			}
			var that = event.node.feed;
			Feeds.clearEntries();
			that.date = new Date();
			$("#entries").empty();
			that.getEntries(false);
		}}}
	);

	t.bind(
		'tree.move',
		function(event){{{
			/* event.move_info.moved_node
			 * event.move_info.target_node
			 * event.move_info.position: ['before','after','inside']
			 * event.move_info.previous_parent
			 */
			var data = {};
			if (event.move_info.position == 'after'){
				data = { moveAfterFeed: event.move_info.target_node.feed.data.ID };
			}
			else if (event.move_info.position == 'before'){
				data = { moveBeforeFeed: event.move_info.target_node.feed.data.ID };
			}
			else if (event.move_info.position == 'inside'){
				data = { moveIntoCategory: event.move_info.target_node.feed.data.ID };
			}
			$.ajax({
				url: "rest.php/feed/"+event.move_info.moved_node.feed.data.ID+"/move",
				type: "POST",
				dataType: "json",
				data: data,
				success: function(data) {
					if (data.status != "OK"){
						alert(data.msg);
						return;
					}
					that.get();
				}
			});
		}}}
	);

	if ($("#buttonShowFavicons input").prop("checked")){
		$(".favicon").show();
	} else {
		$(".favicon").hide();
	}
	that.tree = t;
	resize();
	getTags();
}}}
Feeds.clearEntries = function(){{{
	/* Reset all feeds' entries Object.
	 * This is necessary for correct function of MarkAllRead
	 */
	$.each(Feeds.feeds, function(k,v){
		if (v.entries){ delete(v.entries); }
		v.entries = new Object();
	});
}}}
function EntriesScrolled(event){{{
	if (!($("#buttonAutoAdvance input").prop("checked")))
		return true;
	if (this.scrollTop + 15 > this.scrollTopMax)
		$("#entry_loadMore").click();
	return true;
}}}

function Entry(data){{{
	var that = this;
	this.data = data;
	this.show = EntryShow;
	this.markRead = EntryMarkRead;
	this.toggleRead = EntryToggleRead;
	this.render = EntryRender;
	this.update = EntryUpdate;
	this.addTag = EntryAddTag;
	this.toggleFavorite = EntryToggleFavorite;

	this.data.feed = Feeds.feeds[parseInt(this.data.feedID)];
	this.data.feed.entries[this.data.ID] = this;

	return;
}}}
function EntryAddTag(event){{{
	var that = event.data;
	var tag = $("#newTag_"+that.data.ID).val();
	that.spin.spin("tiny");
	$.ajax({
		url: "rest.php/entry/"+that.data.ID+"/tags",
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ tags: tag }),
		complete: function(){ getTags(); that.spin.spin(false); resize(); /* necessary here because new tag might change height a bit */ },
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}
			$.each(tag.split(","), function(k,v){
				that.tags[that.tags.length] = new Tag(that, {ID: 0, tag: v});
			});
		}
	});
}}}
function EntryMarkRead(){{{
	if (this.data.isread == "1"){
		return;
	}
	this.toggleRead();
}}}
function EntryRender(){{{
	var that = this;
	if (this.data.isread == "1"){
		this.span.removeClass("new");
	} else {
		this.span.addClass("new");
	}
}}}
function EntryShow(){{{
	var that = this;
	that.spin.spin("tiny");
	that.headline.find("#entry_"+that.data.ID).prepend("Loading... ");
	$.ajax({
		url: "rest.php/entry/"+this.data.ID,
		type: "GET",
		dataType: "json",
		complete: function(){ that.spin.spin(false) },
		success: function(data){
			if (data.status == "error"){
				alert(data.msg);
				return;
			}
			that.content.html(data.description.replace(/<(\/?)script/, "<$1disabledscript"));
			that.content.find("img").prop("align", "");
			that.headline.empty().attr("href", data.link).append("<div>"+data.title.replace(/</, "&lt;").replace(/>/, "&gt;")+"</div>");
			resize(); /* necessary here because the form is a bit taller than the button */
			var addTag = $("<a class='label label-default' href='#'><i class='icon-tags'></i></a>"); // TODO
			addTag.one("click", function(){
				var form = $("<form><div class='input-group'><input id='newTag_"+that.data.ID+"' class='input-small form-control' placeholder='New tags...'><span class='input-group-btn'><button type='submut' class='btn btn-default'><i class='icon-tags'>&nbsp;</i></button></span></div></form>");
				form.on("submit", that, that.addTag);
				addTag.after(form);
				addTag.remove();
			});
			that.content.prepend("<br />");
			that.content.prepend(addTag);
			that.tagsHtml = $("<div />");
			that.content.prepend(that.tagsHtml);
			that.tags = new Object();
			that.data.tags = data.tags;
			if (data.tags.length){
				for (var i = 0; i < data.tags.length; i++){
					that.tags[data.tags[i].ID] = new Tag(that, data.tags[i]);
				}
			}
			that.markRead();
		}
	})
}}}
function EntryToggleFavorite(){{{
	var that = this;

	$.ajax({
		url: "rest.php/entry/"+that.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ favorite: (that.iconFav.hasClass("icon-star-empty") ? "yes" : "no") }),
		complete: function(){ getFavoritesCount(); },
		success: function(data){
			if (data.status == "OK"){
				that.iconFav.toggleClass("icon-star icon-star-empty");
			} else {
				alert(data.msg);
			}
		},
	});
}}}
function EntryToggleRead(){{{
	var that = this;
	if (this.data.isread == "1"){
		this.data.isread = "0";
	} else {
		this.data.isread = "1";
	}
	this.render();
	this.spin.spin("tiny");
	$.ajax({
		url: "rest.php/entry/"+that.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({ isread: that.data.isread }),
		complete: function(){ that.spin.spin(false); },
		success: function(data){
			if (data.status == "OK"){
				var f;
				for (f = that.data.feed; f; f=f.parent){
					var i = parseInt(f.data.unreadCount);
					if (that.data.isread == "0"){
						i++;
					} else {
						i--;
					}
					f.data.unreadCount = i;
					f.renderCount();
				}
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function EntryUpdate(){{{
	if (this.data.isread == "0"){
		this.span.addClass("new");
	} else {
		this.span.removeClass("new");
	}
}}}

function Feed(data){{{
	var that = this;
	$.each(["ID","endID","startID","maxID","unreadCount"], function(k,v){
		if (data[v]){
			data[v] = parseInt(data[v]);
		} else {
			data[v] = 0;
		}
	});

	this.data = data;
	this.getEntries=FeedGetEntries;
	this.updateCount=FeedUpdateCount;
	this.markAllRead=FeedMarkAllRead;
	this.renderCount=FeedRenderCount;
	this.showSettings=FeedShowSettings;
	this.deleteFeed=FeedDeleteFeed;
	this.updateFeed=FeedUpdateFeed;
	this.collapse=FeedCollapse;
	this.SQLDate=FeedSQLDate;
	this.jqTree=FeedJqTree;
	this.directories=FeedDirectories;
	this.children = new Array();

	if (this.data.startID > 1){
		/* unless this is the root feed, we need a pointer to our parent feed */
		var keys = [];
		for (k in Feeds.feeds){
			keys.push(k);
		};

		/* First, find all nodes above this one */
		var parents =
			$.grep(keys, function(i, n){
				var f = Feeds.feeds[i];
				return f.data.startID <= that.data.startID &&
					f.data.endID >= that.data.endID &&
					f.isDirectory;
			});

		/* Now, search for the parent with the smallest "gap"
		 * between endID and startID. By definition, that is
		 * our direct parent.
		 */
		min = Feeds.root.data.endID + 1;
		for (idx in parents){
			var f = Feeds.feeds[parents[idx]];
			var diff = f.data.endID - f.data.startID;
			if (diff < min){
				min = diff;
				that.parent = f;
			}
		}
		that.parent.children.push(that);
	}

	/* unfortunately, any of these tend to be sent from the server */
	if ("x"+that.data.url == "x" || "x"+that.data.url == "xnull" || "x"+that.data.url == "xundefined"){
		that.isDirectory=true;
	} else {
		that.isDirectory=false;
	}

	return;
}}}
function FeedCollapse(collapse){{{
	var that = this;
	/* This might return false on when called from get()
	 * Collapsed state is stored serverside and we don't need to update it to its current value
	 */
	if ((this.data.collapsed == "yes") != collapse){
		$.ajax({
			url: "rest.php/feed/"+this.data.ID,
			type: "PUT",
			dataType: "json",
			data: JSON.stringify({
				collapsed: (collapse ? "yes" : "no")
			}),
			success: function(data){
				if (data.status != "OK"){
					alert(data.msg);
					return;
				}
				that.data.collapsed = (collapse ? "yes" : "no");
			}
		});
	}
}}}
function FeedDeleteFeed(){{{
	var that = this;
	that.spin.spin("tiny");

	$.ajax({
		url: "rest.php/feed/"+that.data.ID,
		type: "DELETE",
		dataType: "json",
		success: function(data){
			that.spin.spin(false);
			Feeds.get();
		},
		complete: function(){
			$("#feedSettings").modal("hide");
		}
	});
}}}
function FeedDirectories(){{{
	/* returns all directories below current directory, _including_ current directory */
	var that = this;

	if (!this.isDirectory){
		return [];
	}

	var retVal = [this];
	for (var ptr = 0; ptr < this.children.length; ptr++){
		if (this.children[ptr].isDirectory){
			retVal = retVal.concat(this.children[ptr].directories());
		}
	}
	return retVal;
}}}
function FeedGetEntries(append){{{
	var that = this;
	if (curFeed != that){
		Feeds.clearEntries();
	}

	that.spin.spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+that.data.ID+"/entries/"+that.SQLDate(),
		type: "GET",
		dataType: "json",
		success: function(data){
			if (data.length == 0){
				tree = [];
				tree.push({
					id: "noLoadMore",
					label: "[ No more entries ]"
				});
				showEntries(tree, true);
				return;
			}

			var tree = parseEntries(data);
			oldDate = data[0].date.match(/^(....)-0?(.?.)-0?(.?.)/)[0];
			tree.push(
				{
					id: "loadMore",
					label: "[ Load next day ]",
					entry: undefined,
					feed: that,
					oldDate: oldDate,
					action: function(event){
						var that = event.data.feed;
						var d = event.data.oldDate.match(/^(....)-0?(.?.)-0?(.?.)/);
						that.date = new Date(parseInt(d[1]), parseInt(d[2])-1, parseInt(d[3])-1);
						event.data.feed.getEntries(true);
					}
				}
			);
			showEntries(tree, append);
			curFeed = that;
			if ($("#content")[0].scrollTopMax == 0){
				$("#entry_loadMore").click();
			}
		},
		complete: function(){
			that.spin.spin(false);
			$("#entries").parent().spin(false);
		}
	});
}}}
function FeedJqTree(){{{
	/* Recursive function returning an object suitable to pass to JqTree */
	var that = this;

	var retVal = { id: that.data.ID, feed: that, label: that.data.name };
	retVal.movable = (that != Feeds.root);
	if (that.children.length){
		retVal["children"] = [];
		for (var i = 0; i < that.children.length; i++){
			retVal.children[retVal.children.length] = that.children[i].jqTree();
		}
	}
	return retVal;
}}}
function FeedMarkAllRead(){{{
	var that = this;
	var maxID;

	/* We're using the maximum entry ID we have to prevent the following race condition:
	 * Client gets entries
	 * Server updates feeds, gets new entries into database
	 * Client "marks all as read" which would also cause entries unknown to the client to be marked
	 */
	if (!(maxID = that.data.maxID)){
		maxID = 0;
	}

	that.spin.spin("tiny");
	if (that.entries){
		$.each(that.entries, function(k,v){
			if (!v){
				return true;
			}
			maxID = Math.max(maxID, v.data.ID);
		});
	}

	$.ajax({
		url: "rest.php/feed/"+this.data.ID+"/markAllRead",
		dataType: "json",
		type: "POST",
		data: { maxID: maxID },
		complete: function(){
			that.updateCount();
			that.spin.spin(false);
		},
		success: function(data){
			if (data.status == "OK"){
				$.each(Feeds.feeds, function(k,f){
					if (f.data.startID >= that.data.startID && f.data.endID <= that.data.endID){
						if (f.entries){
							$.each(f.entries, function(k,v){
								v.data.isread="1";
								v.update();
							});
						}
					}
				});
			} else {
				alert(data.msg);
			}
		}
	});
}}}
function FeedRenderCount(){{{
	var that = this;
	that.buttons.newMessage.empty();
	if (that.data.unreadCount > 0){
		that.buttons.newMessage.append(that.data.unreadCount);
		that.nameFeed.addClass("new");
	} else {
		that.nameFeed.removeClass("new");
	}
	if (that.data.startID == 1){ /* All Feeds element */
		if (!isNaN(that.data.unreadCount)){
			$("#numNew_specialUnread").empty().append(that.data.unreadCount);
		}
	}
}}}
function FeedSQLDate(){{{
	return this.date.getFullYear()+"-"+(this.date.getMonth()+1 < 10 ? "0" : "")+(this.date.getMonth()+1)+"-"+(this.date.getDate() < 10 ? "0" : "")+this.date.getDate();
}}}
function FeedShowSettings(){{{
	var that = this;

	var d = $("#feedSettings");
	d.find("#name").val(that.data.name);
	d.find("#url").val(that.data.url).attr("disabled", that.isDirectory);
	d.find("#isgroup").unbind("switch-change")
			  .on("switch-change", function(e, data){
				  var value = data.value;
				  d.find("#url").attr("disabled", value == "1");
			  }).switch("setState", that.isDirectory);
	d.find("#cacheimages").switch("setState", that.data.cacheimages == "yes");
	d.find("#unreadOnChange").switch("setState", that.data.unreadOnChange == "yes");
	d.find("#deleteFeed").button().on("click", function(){
		if (confirm("Really delete feed? This cannot be undone!")){
			if (that.data.startID == 1){
				alert("Cowardly refusing to delete root category '"+that.data.name+"'");
			}
			else if (that.data.endID - 1 != that.data.startID){ // non-empty category
				alert("Cowardly refusing to delete non-empty category!");
			} else {
				d.dialog("close");
				that.deleteFeed();
			}
		}
	});

	var filter = d.find("#filter");
	filter.empty().spin("tiny");
	$.ajax({
		url: "rest.php/feed/"+that.data.ID+"/filter",
		type: "GET",
		dataType: "json",
		success: function (data){
			var s = "";
			data[data.length] = {ID: 0, regex: "", whiteorblack: "white"};
			$.each(data, function(k,v){
				s += "<tr><td>"+
					"<select class='selectpicker' name='whiteorblack_"+v.ID+"' id='whiteorblack_"+v.ID+"'>"+
					"<option value='white' "+(v.whiteorblack == "white" ? "selected" : "")+">Whitelist</option>"+
					"<option value='black' "+(v.whiteorblack == "black" ? "selected" : "")+">Blacklist</option>"+
					"<option value='ignore' "+(v.whiteorblack == "ignore" ? "selected" : "")+">Ignore entry</option>"+
					"</select>"+
					"</td><td>"+
					"<input type='text' value='"+v.regex+"' class='form-control' name='regex_"+v.ID+"'>"+
					"</td><td>"+
					"<div id='delete_"+v.ID+"' class='switch switch-danger switch-small' data-on-label='Delete' data-off-label='Keep'>"+
					"<input type='checkbox' checked='false'>"+
					"</div>"+
					"</td></tr>";
			});
			filter.append(s).spin(false);
			$.each(data, function(k,v){
				filter.find("#delete_"+v.ID).switch();
				filter.find("#delete_"+v.ID).switch("setState", false);
			});
			filter.find(".selectpicker").selectpicker();
		},
		complete: function(){ d.find("input[type=radio]").button(); d.find("input[type=checkbox]").button(); }
	});

	$("#buttonDeleteFeed").unbind("click").bind("click", function(){ if (confirm("Really delete feed? This cannot be undone!")){ that.deleteFeed(); } });
	$("#buttonSaveChanges").unbind("click");
	$("#buttonSaveChanges").bind("click", function(){ that.updateFeed(); });
	if (that.isDirectory){
		$("#buttonAddRSSHandler").show().unbind("click").bind("click", function(){
			if (confirm("Add Feed handler to Firefox and place new feeds under "+that.data.name+"?")){
				var l = window.location;
				var address = l.protocol+"/"+l.hostname+l.pathname.replace("index.html", "")+"add.html?url=%s&parentID="+that.data.ID;
				window.navigator.registerContentHandler('application/vnd.mozilla.maybe.feed',address,"blindRSS " + that.data.name);
			}
		});
	} else {
		$("#buttonAddRSSHandler").hide();
	}
	$("#feedSettings").modal();
}}}
function FeedUpdateCount(){{{
	var that = this;

	$("#spin_Refresh").addClass("icon-spin");

	/* In theory, this can be used for updating single feeds
	 * or containers. In practice, there is only a GUI for
	 * updating the root container.
	 */
	$.ajax({
		url: "rest.php/unreadcount/"+that.data.ID,
		dataType: "json",
		type: "GET",
		data: { _time: new Date() },
		complete: function(){
			$("#spin_Refresh").removeClass("icon-spin");
		},
		success: function(data){
			$.each(data, function(k, v){
				var feed = Feeds.feeds[parseInt(v.ID)];
				if (!feed){
					Feeds.get(); // This feed is missing, probably added via firefox rss handler or another instance.
					return false;
				}
				feed.data.unreadCount = parseInt(v.unread);
				feed.data.maxID = parseInt(v.maxID);
				feed.renderCount();
			});
			for (var x = that.parent; x; x=x.parent){
				/* Only need to update this feeds parents because we only get the unreadcount for this feeds children */
				var newCount = 0;
				for (var c = 0; c < x.children.length; c++){
					newCount += x.children[c].data.unreadCount;
				}
				x.data.unreadCount = newCount;
				x.renderCount();
			}
		}
	});
}}}
function FeedUpdateFeed(){{{
	var that = this;
	var d = $("#feedSettings");
	var filter = new Array();
	$.each(d.find("[name^=regex_]"), function(k,v){
		var id = v.name.split("_")[1];
		var regex = v.value;
		var wob = d.find("select[name=whiteorblack_"+id+"] :selected").val();
		var del = d.find("#delete_"+id+" input").prop("checked") ? "true" : "false";
		filter[filter.length] = {ID: id, regex: regex, whiteorblack: wob, delete: del};
	});
	$.ajax({
		url: "rest.php/feed/"+that.data.ID,
		type: "PUT",
		dataType: "json",
		data: JSON.stringify({
			name: d.find("#name").val(),
			url: (d.find("#isgroup input").prop("checked") ? "" : d.find("#url").val()),
			cacheimages: d.find("#cacheimages input").prop("checked") ? "yes" : "no",
			unreadOnChange: d.find("#unreadOnChange input").prop("checked") ? "yes" : "no",
			filter: filter
		}),
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}
			Feeds.get();
		},
		complete: function(){
			d.modal("hide");
		}
	});
}}}

function Tag(entry, data){{{
	var that = this;
	this.remove = TagRemove;
	this.entry = entry;
	this.data = data;

	var a = $("<a title='Delete tag' href='#'>&times;</a>").on('click', function(){ that.remove() });
	var i = 0;
	if (that.entry.tags)
		i = Object.keys(that.entry.tags).length;
	this.span = $("<span class='label "+(labelNames[i%labelNames.length])+"'>#"+that.data.tag+" </span>")
			.append(a);

	that.entry.tagsHtml.append(this.span);
}}}
function TagRemove(){{{
	var that = this;
	$.ajax({
		url: "rest.php/entry/"+that.entry.data.ID+"/tags/"+that.data.tag,
		type: "DELETE",
		dataType: "json",
		complete: function(){ getTags(); },
		success: function(data){
			that.span.remove();
			resize(); /* necessary here because height might change a bit */
			var i = 0;
			$.each($("#headlineTags > .label"), function(k,v){
				v = $(v);
				for (var j=0; j<labelNames.length; j++){
					v.removeClass(labelNames[j]);
				}
				v.addClass(labelNames[i]);
				i++;
			});
		}
	});
}}}

function addFeed(){{{
	var d = $("#addFeed");
	$.ajax({
		url: "rest.php/feeds",
		type: "POST",
		dataType: "json",
		data: {
			parent: d.find("#parent").val(),
			name: d.find("#name").val(),
			url: (d.find("#isgroup input").prop("checked") ? "" : d.find("#url").val()),
			cacheimages: d.find("#cacheimages input").prop("checked") ? "yes" : "no",
			unreadOnChange: d.find("#unreadOnChange input").prop("checked") ? "yes" : "no"
		},
		success: function(data){
			if (data.status == "OK"){
				Feeds.get();
				d.modal("hide");
				return;
			}
			alert ("Error: "+data.msg);
			return;
		}
	});
}}}
function cronjob(){{{
	var reloadEvery = $("#selectReloadEvery").val();
	if (reloadEvery == "other"){
		reloadEvery = $("#reloadEveryOther").val();
	}
	reloadEvery = parseInt(reloadEvery);
	if (reloadEvery <= 0){
		return;
	}

	var now = new Date();
	console.log("cronjob: checking " + now + " - " + cronjobData.lastRun + " >= " + (reloadEvery * 60000));
	if (now - cronjobData.lastRun >= reloadEvery * 60000){
		Feeds.root.updateCount();
		cronjobData.lastRun = now;
	}
}}}
function getEntries(url, spin){{{
	Feeds.clearEntries();

	if (typeof spin != "function"){
		spin = "#"+spin;
		$(spin).spin("tiny");
	}
	$.ajax({
		url: url,
		type: "GET",
		dataType: "json",
		complete: function(){
			if (typeof spin != "function"){
				$(spin).spin(false);
			} else {
				spin();
			}
		},
		success: function(data){
			showEntries(parseEntries(data));
		}
	});
}}}
function getFavoritesCount(){{{
	$.ajax({
		url: "rest.php/favorites/count",
		type: "GET",
		dataType: "json",
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}
			$("#numNew_specialFavorites").empty().append(data.num);
		}
	});
}}}
function getOptions(){{{
	$.ajax({
		url: "rest.php/options",
		type: "GET",
		dataType: "json",
		success: function(data){
			$("#selectPurgeAfter").val(data.purgeAfter.value);
			$("#selectPurgeAfter").on("change", function(e){
				var value = $("#selectPurgeAfter").val();
				setOption("purgeAfter", value);
			});
			$("#buttonDeleteFavorites").attr("checked", data.deleteFavorites.value == "yes" ? "checked" : "")
						  .switch("setState", data.deleteFavorites.value == "yes");
			$("#buttonDeleteFavorites").on("switch-change", function(e, data){
				var value = data.value;
				setOption("deleteFavorites", value ? "yes" : "no");
			});
			$("#buttonDeleteTagged").attr("checked", data.deleteTagged.value == "yes" ? "checked" : "")
						  .switch("setState", data.deleteTagged.value == "yes");
			$("#buttonDeleteTagged").on("switch-change", function(e, data){
				var value = data.value;
				setOption("deleteTagged", value ? "yes" : "no");
			});
			$("#buttonAutoAdvance").attr("checked", data.autoAdvance.value == "yes" ? "checked" : "")
						  .switch("setState", data.autoAdvance.value == "yes");
			$("#buttonAutoAdvance").on("switch-change", function(e, data){
				var value = data.value;
				setOption("autoAdvance", value ? "yes" : "no");
			});
			$("#buttonShowFavicons").attr("checked", data.autoAdvance.value == "yes" ? "checked" : "")
						  .switch("setState", data.showFavicons.value == "yes");
			$("#buttonShowFavicons").on("switch-change", function(e, data){
				var value = data.value;
				setOption("showFavicons", value ? "yes" : "no");
				if (value){
					$(".favicon").show();
				} else {
					$(".favicon").hide();
				}
			});
			$("#buttonTextOrIcons").attr("checked", data.textOrIcons.value == "text" ? "checked" : "")
						  .switch("setState", data.textOrIcons.value == "text");
			toggleTextOrIcons(data.textOrIcons.value);
			$("#buttonTextOrIcons").on("switch-change", function(e, data){
				var value = data.value;
				setOption("textOrIcons", value ? "text" : "icons");
				toggleTextOrIcons(value ? "text" : "icons");
			});
			if (data.reloadEvery.value == "-1" ||
					data.reloadEvery.value == "15" ||
					data.reloadEvery.value == "30" ||
					data.reloadEvery.value == "60"){
					$("#selectReloadEvery").val(data.reloadEvery.value);
					$("#reloadEveryOther").hide();
			} else {
				$("#selectReloadEvery").val("other");
				$("#reloadEveryOther").val(data.reloadEvery.value).show();
			}
			if (parseInt(data.reloadEvery.value) > 0){
				var now = new Date();
				cronjobData.lastRun = new Date(now - (now % (parseInt(data.reloadEvery.value) * 60000)));
			}
			$("#selectReloadEvery").on("change", function(){
				var val = -1;
				if ($(this).val() == "other"){
					$("#reloadEveryOther").show();
				} else {
					$("#reloadEveryOther").hide();
					setOption("reloadEvery", $(this).val());
				}
			});
			$("#reloadEveryOther").popover({
				trigger: "focus",
				placement: "left",
				content: "Enter reload time in whole minutes:"
			});
			$("#reloadEveryOther").on("change", function(){
				setOption("reloadEvery", $(this).val());
			});
		}
	});
}}}
function getTag(tagID){{{
	return getEntries("rest.php/tags/"+tagID, "spinFeed_tag"+tagID);
}}}
function getTags(){{{
	$.ajax({
		url: "rest.php/tags",
		type: "GET",
		dataType: "json",
		success: function(data){
			if (data.status != "OK"){
				alert(data.msg);
				return;
			}

			var parent_node = Feeds.tree.tree("getNodeById", "specialTags");

			for (var i = 0; i < data.tags.length; i++){
				if ($("#numNew_tag"+data.tags[i].ID).length){
					$("#numNew_tag"+data.tags[i].ID).empty().append(data.tags[i].num);
					continue;
				}
				var t = data.tags[i].tag;
				var tID = data.tags[i].ID;
				var num = data.tags[i].num;
				Feeds.tree.tree("appendNode",
					{ id: "tag"+tID,
						tagID: tID,
						label: t,
						num: num,
						action: function(event){ getTag(event.node.tagID); }
					},
					parent_node);
			}
		},
		complete: function(){
			getFavoritesCount();
		}
	});
}}}
function parseEntries(data){{{
	var oldDate;
	var tree = [];
	$.each(data, function(k, v){
		var newDate = v.date.match(/^(....)-0?(.?.)-0?(.?.)/);
		newDate = newDate[0];
		if (newDate != oldDate){
			tree.push({id: "date_"+newDate, label: newDate, entry: undefined, action: function(e){ return; }});
			oldDate = newDate;
		}
		var e = new Entry(v);
		e.data.title = $("<div />").html(e.data.title).text();
		tree.push({id: e.data.ID, label: Feeds.feeds[e.data.feedID].data.name+": "+e.data.title, entry: e});
	});
	return tree;
}}}
function resize(){{{
	$.each(["#content", "#feeds"], function(k,v){
		var x = $(v);
		x.height(window.innerHeight - (x.offset().top + 10));
	});
}}}
function search(){{{
	$("#spin_search").toggleClass("icon-search icon-spin icon-spinner");
	$("#frmSearch [type=submit]").attr("disabled", "disabled");
	getEntries("rest.php/search/"+encodeURIComponent($("#frmSearch #txtSearch").val()), function(){
		$("#spin_search").toggleClass("icon-search icon-spin icon-spinner");
		$("#frmSearch [type=submit]").attr("disabled", false);
	});
}}}
function setOption(key, value){{{
	if (key == "purgeAfter"){
		if (!confirm("Really purge entries after "+value+" days?")){
			return;
		}
	}
	$.ajax({
		url: "rest.php/options/"+key,
		type: "POST",
		dataType: "json",
		data: { value: value },
		success: function(data) {
			if (data.status == "error"){
				alert(data.msg);
			}
		}
	});
}}}
function showAddFeed(){{{
	var d = $("#addFeed");

	var sortedFeeds = Feeds.root.directories();

	var options = $("<select id='parent' class='selectpicker' />");
	$.each(sortedFeeds, function(k,v){
		var s = "";
		for (var x = v; x; x=x.parent){
			s = "/"+x.data.name+s;
		}
		options.append("<option value='"+v.data.ID+"'>"+s+"</option>");
	});
	options.val(Feeds.root.data.ID);
	d.find("#controlParent").empty().append(options);
	options.selectpicker();
	d.find("#buttonAddFeed").unbind("click").bind("click", addFeed);
	d.find("#isgroup").switch("setState", false);
	d.modal();
}}}
function showEntries(tree, append=false){{{
	var dBody = $("#content");
	globalEntriesTreeData = tree;
	if (append){
		$("#content .panel:last").remove();
		$("#content").append("<br />");
	} else {
		dBody.empty();
		dBody.scrollTop(0);
	}

	for (var i = 0; i < tree.length; i++){
		var div = $("<div class='panel panel-default'>");
		var divheading = $("<div class='panel-heading'><a href='#'><div id='entry_"+tree[i].id+"'>"+tree[i].label+"</div></a></div>");

		if (tree[i].entry != null){
			var entry = tree[i].entry;
			var iconNew = $("<i class='newmessage floatLeft icon-asterisk' />")
				.on("click", null, entry, function(event){
					event.data.toggleRead();
					return false;
				});
			var iconFav = $("<i class='floatLeft icon-star-empty' />")
				.on("click", null, entry, function(event){
					event.data.toggleFavorite();
					return false;
				});
			if (entry.data.favorite == "yes"){
				iconFav.toggleClass("icon-star icon-star-empty");
			}
			if (entry.data.isread == "0"){
				divheading.addClass("new");
			} else {
				divheading.removeClass("new");
			}

			entry.spin = $("<span class='floatLeft spin' id='spinEntry_"+entry.data.ID+"'>&nbsp;</span>");
			entry.iconFav = iconFav;
			entry.iconNew = iconNew;
			entry.span = divheading;
			entry.content = $("<div>").appendTo(div);
			entry.headline = divheading.find("a");

			divheading
				.prepend($("<a href='#' />").append(iconFav))
				.prepend($("<a href='#' />").append(iconNew))
				.prepend(entry.spin);
		}
		divheading.prependTo(div);
		divheading.one('click', "a", tree[i], function(event){
			if (event.data.action){
				event.data.action(event);
			}
			if (event.data.entry){
				event.data.entry.show();
			}
		});
		div.appendTo(dBody);
	}
}}}
function startup(){{{
	/* Create bootstrap theme */
	$("input[type=button]").button();
	$(".selectpicker").selectpicker();

	$("#btnAddNewTag").on("click", function(){
		$("#btnAddNewTag").hide();
		$("#frmAddNewTag").show();
		resize(); /* necessary here because the form is a bit higher than the button */
	});

	$("#content").on("scroll", EntriesScrolled);

	$("#importOPML").fileupload({ // {{{
		url: "rest.php/opml",
		dataType: "json",
		add: function(e, data){
			data.context = $('#buttonImportOPML').text('Upload').prop("disabled", false)
			.one("click", function(){
				$("#importOPML").fileupload("option", "url", "rest.php/opml/"+$("#importOPMLParent").val());
				data.context.text("Uploading...").prop("disabled", true);
				data.submit();
			});

			var sortedFeeds = Feeds.root.directories();

			var options = $("select#importOPMLParent").empty().append("<option>[Select parent]</option>");
			$.each(sortedFeeds, function(k,v){
				var s = "";
				for (var x = v; x; x=x.parent){
					s = "/"+x.data.name+s;
				}
				options.append("<option value='"+v.data.ID+"'>"+s+"</option>");
			});
			options.val(Feeds.root.data.ID).prop("disabled", false);
		},
		done: function(data){
			$("#buttonImportOPML").text('Upload finished.');
			Feeds.get();
		}
	}); // }}}

	Feeds.get();
	getOptions();
	resize();
	window.onresize=resize;
}}}
function toggleTextOrIcons(textOrIcons){{{
	if (textOrIcons == "icons"){
		$(".titleText").hide();
		$(".titleIcon").show();
	} else {
		$(".titleText").show();
		$(".titleIcon").hide();
	}
}}}

$(document).ready(startup);
