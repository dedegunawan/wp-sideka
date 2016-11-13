<?php
/**
 * Created by PhpStorm.
 * User: F
 * Date: 9/23/2016
 * Time: 3:51 AM
 */

$desa_id = "papayan";
$server_name = $_SERVER["SERVER_NAME"];
$server_splits = explode(".", $server_name);
if($server_splits[0].".desa.id" == $server_name){
    $desa_id = $server_splits[0];
}

$ckan_host = "http://data.prakarsadesa.id";
#$ckan_host = "http://ckan.neon.microvac:5000";
$package_id = $desa_id."-keuangan";
$json = @file_get_contents($ckan_host . '/api/3/action/package_show?id=' . $package_id);
$package_exists = json_decode($json)->success;
?>
<?php if($package_exists) { ?>
    <style>
        #count-summary {
            width: 100%;
            text-align: center;
            margin: -15px 0 0 0;
        }
        #count-summary dt {
            text-transform: uppercase;
            margin-top: 15px;
            list-style-type: none;
            margin-left: 0;
            font-weight: normal;
            font-size: 16px;
        }
        #count-summary dd {
            list-style-type: none;
            font-size: 16px;
            margin-left: 0;
        }
        #count-summary.larger dd {
            font-size: 30px;
        }
    </style>

    <style>

        #details {
            background: #fff;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        .title {
            font-weight: bold;
            font-size: 24px;
            text-align: center;
            margin-top: 6px;
            margin-bottom: 6px;
        }
        text {
            pointer-events: none;
        }

        .grandparent text {
            font-weight: bold;
        }

        rect {
            fill: none;
            stroke: #fff;
        }

        rect.parent,
        .grandparent rect {
            stroke-width: 2px;
        }

        rect.parent {
            pointer-events: none;
        }

        .grandparent rect {
            fill: orange;
        }

        .grandparent:hover rect {
            fill: #ee9700;
        }

        .children rect.parent,
        .grandparent rect {
            cursor: pointer;
        }

        .children rect.parent {
            fill: #bbb;
            fill-opacity: .5;
        }

        .children:hover rect.child {
            fill: #bbb;
        }

        .mh-widget-title-link select {
            border: 0 none;
            font-weight: 600;
            color: #e64946;
            font-size: 16px;
            line-height: 1.3;
            font-family: 'Open Sans', Helvetica, Arial, sans-serif;
        }

    </style>

    <div class="clearfix">
        <h4 class="mh-widget-title">
            <span class="mh-widget-title-inner"><a class="mh-widget-title-link">Anggaran Pendapatan dan Belanja Desa Tahun Anggaran 2016</a></span>
        </h4>
        <div class="mh-widget-col-1 mh-sidebar">
            <br /><br />
            <dl id="count-summary" class="larger">
                <dt class="required">Pendapatan</dt>
                <dd id="count-family">Rp. 1.143.161.861</dd>
            </dl>
        </div>
        <div class="mh-widget-col-1 mh-sidebar">
            <br /><br />
            <dl id="count-summary" class="larger">
            <dt class="required">Belanja</dt>
            <dd id="count-family">Rp. 1.143.161.861</dd>
            </dl>
        </div>
        <div class="mh-widget-col-1 mh-sidebar">
            <dl id="count-summary">
                <dt class="required">Defisit</dt>
                <dd id="count-family">Rp. 1.143.161.861</dd>
                <dt class="required">Pembiayaan</dt>
                <dd id="count-family">Rp. 1.143.161.861</dd>
                <dt class="required">Pengeluaran Pembiayaan</dt>
                <dd id="count-family">Rp. 1.143.161.861</dd>
            </dl>
        </div>
    </div>
    <br />
    <div class="clearfix">
        <h4 class="mh-widget-title">
            <span class="mh-widget-title-inner"><a class="mh-widget-title-link">Rincian Belanja Desa <select id="year-selector">
                    </select></a></span>
        </h4>
        <div id="details" style="width: 100%; height: 700px;">
        </div>
    </div>


    <link href="http://nvd3.org/assets/css/nv.d3.css" rel="stylesheet">
    <script src="http://nvd3.org/assets/lib/d3.v3.js"></script>
    <script src="http://nvd3.org/assets/js/nv.d3.js"></script>

    <script type="text/javascript">
        document.getElementsByClassName("entry-header")[0].remove();
        var package_id = "<?= $package_id ?>";
        var ckan_host = "<?= $ckan_host ?>";
        var package = <?= $json ?>;
        var years = [];
        var apbdeses = package.result.resources
            .filter(function(r) {return r.name.startsWith("APBDes ")});
        apbdeses.forEach(function(apbdes){
                d3.csv(ckan_host + apbdes.url, function(error, data) {
                    var year = apbdes.name.substring(7);
                    showApbdes(year, apbdes, data);
                    years.push(year)
                    if(years.length == apbdeses.length){
                        finalizeApbdes();
                    }
                });
        });
        function finalizeApbdes(){
            var $ = window.jQuery;
            years.sort().reverse();
            $("#year-selector").change(function(){
                var val = $(this).val();
                $("#details > div").each(function(){$(this).hide();});
                $("#details > div[data-year='"+val+"']").show();
            });
            years.forEach(function(year){
                $("#year-selector").append("<option>"+year+"</option>");
            });
            $("#details > div[data-year='"+years[0]+"']").show();
        }

        function showApbdes(year, apbdes, data){
            window.data = data;
            var belanja = data.filter(function(r){return r.kode_rekening && r.kode_rekening.startsWith("2.");});

            var root = { key: "2", name: "Belanja", values: [] };
            belanja.forEach(function(b){
                var splits = b.kode_rekening.split(".");
                var parentCodes = [];
                if(splits.length >= 3){
                    for(var i = 1; i < splits.length; i++){
                        parentCodes.push(splits.slice(0, i).join("."));
                    }
                }
                var parent = root;
                var found = null;
                for(var i = 0; i < parentCodes.length; i++) {
                    var parentCode = parentCodes[i];
                    if (parent.key == parentCode) {
                        found = parent
                    } else {
                        found = parent.values.filter(function (v) {
                            return v.key == parentCode
                        })[0];
                        if (!found) {
                            console.log("cannot find ", parentCode);
                            return;
                        }
                    }
                    parent = found;
                }

                var val = parseInt(b.anggaran);
                if(!isFinite(val))
                    val = 0;
                parent.values.push({
                    key: b.kode_rekening,
                    name: b.uraian,
                    value: val,
                    values: [],
                });
            });
            var cleanUp = function(node){
                //node.key = node.key + " " + node.name;
                node.key = node.name;
                if(node.values.length){
                    delete node.value;
                    for(var i = 0; i < node.values.length; i++){
                        cleanUp(node.values[i]);
                    }
                } else {
                    delete node.values;
                }
                delete node.name;
            }
            cleanUp(root);
            main({year: year}, root);
            console.log(root);
        }

        var width = d3.select("#details").node().getBoundingClientRect().width;
        var defaults = {
            margin: {top: 24, right: 0, bottom: 0, left: 0},
            rootname: "TOP",
            format: ",d",
            title: "",
            width: width,
            height: 700
        };

        //http://bl.ocks.org/ganeshv/6a8e9ada3ab7f2d88022
        function main(o, data) {
            var $ = jQuery;
            var $div = $("<div></div>").appendTo($("#details")).attr("data-year", o.year);
            var root,
                opts = $.extend(true, {}, defaults, o),
                formatNumber = d3.format(opts.format),
                rname = opts.rootname,
                margin = opts.margin,
                theight = 36 + 16;

            $div.width(opts.width).height(opts.height);
            var width = opts.width - margin.left - margin.right,
                height = opts.height - margin.top - margin.bottom - theight,
                transitioning;

            var color = d3.scale.category20c();

            var x = d3.scale.linear()
                .domain([0, width])
                .range([0, width]);

            var y = d3.scale.linear()
                .domain([0, height])
                .range([0, height]);

            var treemap = d3.layout.treemap()
                .children(function(d, depth) { return depth ? null : d._children; })
                .sort(function(a, b) { return a.value - b.value; })
                .ratio(height / width * 0.5 * (1 + Math.sqrt(5)))
                .round(false);

            var svg = d3.select($div[0]).append("svg")
                .attr("width", width + margin.left + margin.right)
                .attr("height", height + margin.bottom + margin.top)
                .style("margin-left", -margin.left + "px")
                .style("margin.right", -margin.right + "px")
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
                .style("shape-rendering", "crispEdges");

            var grandparent = svg.append("g")
                .attr("class", "grandparent");

            grandparent.append("rect")
                .attr("y", -margin.top)
                .attr("width", width)
                .attr("height", margin.top);

            grandparent.append("text")
                .attr("x", 6)
                .attr("y", 6 - margin.top)
                .attr("dy", ".75em");

            if (opts.title) {
                $div.prepend("<p class='title'>" + opts.title + "</p>");
            }
            if (data instanceof Array) {
                root = { key: rname, values: data };
            } else {
                root = data;
            }

            initialize(root);
            accumulate(root);
            trim(root);
            layout(root);
            console.log(root);
            display(root);
            $div.hide();


            if (window.parent !== window) {
                var myheight = document.documentElement.scrollHeight || document.body.scrollHeight;
                window.parent.postMessage({height: myheight}, '*');
            }

            function initialize(root) {
                root.x = root.y = 0;
                root.dx = width;
                root.dy = height;
                root.depth = 0;
            }

            // Aggregate the values for internal nodes. This is normally done by the
            // treemap layout, but not here because of our custom implementation.
            // We also take a snapshot of the original children (_children) to avoid
            // the children being overwritten when when layout is computed.
            function accumulate(d) {
                return (d._children = d.values)
                    ? d.value = d.values.reduce(function(p, v) { return p + accumulate(v); }, 0)
                    : d.value;
            }

            function trim(d){
                var arr = d.values;
                for(var i = arr.length - 1; i >= 0; i--){
                    var c = arr[i];
                    if(c.value == 0){
                        arr.splice(i, 1);
                    } else {
                        if(c.values)
                            trim(c);
                    }
                }
            }

            // Compute the treemap layout recursively such that each group of siblings
            // uses the same size (1�1) rather than the dimensions of the parent cell.
            // This optimizes the layout for the current zoom state. Note that a wrapper
            // object is created for the parent node for each group of siblings so that
            // the parent�s dimensions are not discarded as we recurse. Since each group
            // of sibling was laid out in 1�1, we must rescale to fit using absolute
            // coordinates. This lets us use a viewport to zoom.
            function layout(d) {
                if (d._children) {
                    treemap.nodes({_children: d._children});
                    d._children.forEach(function(c) {
                        c.x = d.x + c.x * d.dx;
                        c.y = d.y + c.y * d.dy;
                        c.dx *= d.dx;
                        c.dy *= d.dy;
                        c.parent = d;
                        layout(c);
                    });
                }
            }

            function display(d) {
                grandparent
                    .datum(d.parent)
                    .on("click", transition)
                    .select("text")
                    .text(name(d));

                var g1 = svg.insert("g", ".grandparent")
                    .datum(d)
                    .attr("class", "depth");

                var g = g1.selectAll("g")
                    .data(d._children)
                    .enter().append("g");

                g.filter(function(d) { return d._children; })
                    .classed("children", true)
                    .on("click", transition);

                var children = g.selectAll(".child")
                    .data(function(d) { return d._children || [d]; })
                    .enter().append("g");

                children.append("rect")
                    .attr("class", "child")
                    .call(rect)
                    .append("title")
                    .text(function(d) { return d.key + " (" + formatNumber(d.value) + ")"; });
                children.append("text")
                    .attr("class", "ctext")
                    .text(function(d) { return d.key; })
                    .call(text2);

                g.append("rect")
                    .attr("class", "parent")
                    .call(rect);

                var t = g.append("text")
                    .attr("class", "ptext")
                    .attr("dy", ".75em")

                t.append("tspan")
                    .text(function(d) { return d.key; });
                t.append("tspan")
                    .attr("dy", "1.0em")
                    .text(function(d) { return formatNumber(d.value); });
                t.call(text);

                g.selectAll("rect")
                    .style("fill", function(d) { return color(d.key); });

                function transition(d) {
                    if (transitioning || !d) return;
                    transitioning = true;

                    var g2 = display(d),
                        t1 = g1.transition().duration(750),
                        t2 = g2.transition().duration(750);

                    // Update the domain only after entering new elements.
                    x.domain([d.x, d.x + d.dx]);
                    y.domain([d.y, d.y + d.dy]);

                    // Enable anti-aliasing during the transition.
                    svg.style("shape-rendering", null);

                    // Draw child nodes on top of parent nodes.
                    svg.selectAll(".depth").sort(function(a, b) { return a.depth - b.depth; });

                    // Fade-in entering text.
                    g2.selectAll("text").style("fill-opacity", 0);

                    // Transition to the new view.
                    t1.selectAll(".ptext").call(text).style("fill-opacity", 0);
                    t1.selectAll(".ctext").call(text2).style("fill-opacity", 0);
                    t2.selectAll(".ptext").call(text).style("fill-opacity", 1);
                    t2.selectAll(".ctext").call(text2).style("fill-opacity", 1);
                    t1.selectAll("rect").call(rect);
                    t2.selectAll("rect").call(rect);

                    // Remove the old node when the transition is finished.
                    t1.remove().each("end", function() {
                        svg.style("shape-rendering", "crispEdges");
                        transitioning = false;
                    });
                }

                return g;
            }

            function text(text) {
                text.selectAll("tspan")
                    .attr("x", function(d) { return x(d.x) + 6; })
                text.attr("x", function(d) { return x(d.x) + 6; })
                    .attr("y", function(d) { return y(d.y) + 6; })
                    .style("opacity", function(d) { return this.getComputedTextLength() < x(d.x + d.dx) - x(d.x) ? 1 : 0; });
            }

            function text2(text) {
                text.attr("x", function(d) { return x(d.x + d.dx) - this.getComputedTextLength() - 6; })
                    .attr("y", function(d) { return y(d.y + d.dy) - 6; })
                    .style("opacity", function(d) { return this.getComputedTextLength() < x(d.x + d.dx) - x(d.x) ? 1 : 0; });
            }

            function rect(rect) {
                rect.attr("x", function(d) { return x(d.x); })
                    .attr("y", function(d) { return y(d.y); })
                    .attr("width", function(d) { return x(d.x + d.dx) - x(d.x); })
                    .attr("height", function(d) { return y(d.y + d.dy) - y(d.y); });
            }

            function name(d) {
                return d.parent
                    ? name(d.parent) + " / " + d.key + " (" + formatNumber(d.value) + ")"
                    : d.key + " (" + formatNumber(d.value) + ")";
            }
        }
    </script>
<?php } else { ?>

    <p>Data keuangan belum diunggah</p>

<?php } ?>

