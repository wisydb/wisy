// https://observablehq.com/@d3/line-chart-with-tooltip@260
export default function define(runtime, observer) {
  const main = runtime.module();
  const fileAttachments = new Map([["aapl.csv",new URL(window.inputCSV,import.meta.url)]]);
  main.builtin("FileAttachment", runtime.fileAttachments(name => fileAttachments.get(name)));
  main.variable(observer()).define(["md"], function(md){return(
md`# <hr>${window.grtitle}<br><button data-href='${window.inputCSV}' onclick='window.open($(this).attr("data-href")); return false;' class='csv_link'>Download</button>`
)});
  main.variable(observer("chart")).define("chart", ["d3","DOM","width","height","xAxis","yAxis","data","line","bisect","x","y","callout","formatValue","formatDate"], function(d3,DOM,width,height,xAxis,yAxis,data,line,bisect,x,y,callout,formatValue,formatDate)
{
  const svg = d3.select(DOM.svg(width, height))
      .style("-webkit-tap-highlight-color", "transparent")
      .style("overflow", "visible");

  svg.append("g")
      .call(xAxis);

  svg.append("g")
      .call(yAxis);
  
  svg.append("path")
      .datum(data)
      .attr("fill", "none")
      .attr("stroke", window.strokeColor)
      .attr("stroke-width", 1.5)
      .attr("stroke-linejoin", "round")
      .attr("stroke-linecap", "round")
      .attr("d", line);

  const tooltip = svg.append("g");

  svg.on("touchmove mousemove", function(event) {
    const {Datum, Anzahl} = bisect(d3.pointer(event, this)[0]);

    tooltip
        .attr("transform", `translate(${x(Datum)},${y(Anzahl)})`)
        .call(callout, `${formatValue(Anzahl)}
${formatDate(Datum)}`);
  });

  svg.on("touchend mouseleave", () => tooltip.call(callout, null));

  return svg.node();
}
);
  main.variable(observer("callout")).define("callout", function(){return(
(g, Anzahl) => {
  if (!Anzahl) return g.style("display", "none");

  g.style("display", null)
   .style("pointer-events", "none")
   .style("font", "10px sans-serif");

  const path = g.selectAll("path")
    .data([null])
    .join("path")
      .attr("fill", "white")
      .attr("stroke", "black");

  const text = g.selectAll("text")
    .data([null])
    .join("text")
    .call(text => text
      .selectAll("tspan")
      .data((Anzahl + "").split(/\n/))
      .join("tspan")
        .attr("x", 0)
        .attr("y", (d, i) => `${i * 1.1}em`)
        .style("font-weight", (_, i) => i ? null : "bold")
        .text(d => d));

  const {x, y, width: w, height: h} = text.node().getBBox();

  text.attr("transform", `translate(${-w / 2},${15 - y})`);
  path.attr("d", `M${-w / 2 - 10},5H-5l5,-5l5,5H${w / 2 + 10}v${h + 20}h-${w + 20}z`);
}
)});
  main.variable(observer("data")).define("data", ["d3","FileAttachment"], async function(d3,FileAttachment){
  d3.timeFormatDefaultLocale({
        "decimal": ",",
        "thousands": ".",
        "grouping": [3],
        "currency": ["EUR", ""],
        "dateTime": "%a %b %e %X %Y",
        "date": "%d.%m.%Y",
        "time": "%H:%M:%S",
        "periods": ["AM", "PM"],
        "days": ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag"],
        "shortDays": ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
        "months": ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
        "shortMonths": ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"]
    });
    
    return(
Object.assign(d3.csvParse(await FileAttachment("aapl.csv").text(), d3.autoType).map(({Datum, Anzahl}) => ({Datum, Anzahl: Anzahl})), {y: "Anzahl"})
)});
  main.variable(observer("x")).define("x", ["d3","data","margin","width"], function(d3,data,margin,width){return(
d3.scaleUtc()
    .domain(d3.extent(data, d => d.Datum))
    .range([margin.left, width - margin.right])
)});
  main.variable(observer("y")).define("y", ["d3","data","height","margin"], function(d3,data,height,margin){return(
d3.scaleLinear()
    .domain([0, d3.max(data, d => d.Anzahl)]).nice()
    .range([height - margin.bottom, margin.top])
)});
  main.variable(observer("xAxis")).define("xAxis", ["height","margin","d3","x","width"], function(height,margin,d3,x,width){
  return(
g => g
    .attr("transform", `translate(0,${height - margin.bottom})`)
    .call(d3.axisBottom(x).ticks(width / 80).tickSizeOuter(0))
)});
  main.variable(observer("yAxis")).define("yAxis", ["margin","d3","y","data"], function(margin,d3,y,data){return(
g => g
    .attr("transform", `translate(${margin.left},0)`)
    .call(d3.axisLeft(y))
    .call(g => g.select(".domain").remove())
    .call(g => g.select(".tick:last-of-type text").clone()
        .attr("x", 3)
        .attr("text-anchor", "start")
        .attr("font-weight", "bold")
        .text(data.y))
)});
  main.variable(observer("line")).define("line", ["d3","x","y"], function(d3,x,y){return(
d3.line()
    .curve(d3.curveStep)
    .defined(d => !isNaN(d.Anzahl))
    .x(d => x(d.Datum))
    .y(d => y(d.Anzahl))
)});
  main.variable(observer("formatValue")).define("formatValue", function(){return(
function formatValue(Anzahl) {
  return Anzahl;
}
)});
  main.variable(observer("formatDate")).define("formatDate", function(){return(
function formatDate(Datum) {
  return Datum.toLocaleString("de", {
    month: "short",
    day: "numeric",
    year: "numeric",
    timeZone: "UTC"
  });
}
)});
  main.variable(observer("bisect")).define("bisect", ["d3","x","data"], function(d3,x,data)
{
  const bisect = d3.bisector(d => d.Datum).left;
  return mx => {
    const Datum = x.invert(mx);
    const index = bisect(data, Datum, 1);
    const a = data[index - 1];
    const b = data[index];
    return b && (Datum - a.Datum > b.Datum - Datum) ? b : a;
  };
}
);
  main.variable(observer("height")).define("height", function(){return(
500
)});
  main.variable(observer("margin")).define("margin", function(){return(
{top: 20, right: 30, bottom: 30, left: 40}
)});
  main.variable(observer("d3")).define("d3", ["require"], function(require){return(
require("d3@6")
)});
  return main;
}
