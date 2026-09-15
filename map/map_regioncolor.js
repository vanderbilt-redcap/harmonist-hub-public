am5.ready(function () {
    // ========= MAP ROOT =========
    const root = am5.Root.new("chartdiv");
    root.setThemes([am5themes_Animated.new(root)]);

    const chart = root.container.children.push(
        am5map.MapChart.new(root, {
            projection: am5map.geoMercator(),
            panX: "translateX",
            panY: "translateY",
            wheelX: "none",
            wheelY: "zoom"
        })
    );

    window.__am5chart = chart;

    const zoomControl = am5map.ZoomControl.new(root, {});
    zoomControl.homeButton.set("visible", true);
    chart.set("zoomControl", zoomControl);

    // ========= DATA HELPERS =========
    const allAreas = window.jsonAreaDataAll || [];
    const areaById = new Map(allAreas.map((a) => [a.id, a]));

    // ========= POLYGONS =========
    const polygonSeries = chart.series.push(
        am5map.MapPolygonSeries.new(root, {
            geoJSON: am5geodata_worldLow,
            exclude: ["AQ"]
        })
    );

    polygonSeries.mapPolygons.template.setAll({
        interactive: true,
        fill: am5.color(0xd9d9d9),
        fillOpacity: 0.8,
        stroke: am5.color(0xffffff),
        strokeWidth: 1
    });

    polygonSeries.mapPolygons.template.states.create("hover", {
        fill: am5.color(0x808080)
    });

    polygonSeries.mapPolygons.template.adapters.add("fill", (fill, target) => {
        const id = target.dataItem?.get("id");
        const meta = areaById.get(id);
        return meta?.color ? am5.color(meta.color) : fill;
    });

    polygonSeries.mapPolygons.template.adapters.add("tooltipText", (text, target) => {
        const id = target.dataItem?.get("id");
        const meta = areaById.get(id);
        return meta?.title || target.dataItem?.get("name") || text;
    });

    polygonSeries.mapPolygons.template.events.on("click", (ev) => {
        const di = ev.target.dataItem;
        if (di) polygonSeries.zoomToDataItem(di);
    });

    polygonSeries.events.once("datavalidated", () => {
        const regionCode = window.initialRegionCode;
        const z =
            regionCode &&
            window.totalAreasByRegionZoom &&
            window.totalAreasByRegionZoom[regionCode];

        if (!z) return;

        const factor = 3;
        const homePoint = { longitude: Number(z.longitude), latitude: Number(z.latitude) };
        const homeZoom = Math.max(1, Number(z.zoom) * factor);

        chart.zoomToGeoPoint(homePoint, homeZoom, false);
        chart.set("homeGeoPoint", homePoint);
        chart.set("homeZoomLevel", homeZoom);
    });

    // ========= POINTS =========
    const pointSeries = chart.series.push(am5map.MapPointSeries.new(root, {}));

    pointSeries.bullets.push(function () {
        const circle = am5.Circle.new(root, {
            radius: 4,
            fill: am5.color(0x4d4d4d),
            stroke: am5.color(0xffffff),
            strokeWidth: 1,
            tooltipText: "{title}\n{description}"
        });
        return am5.Bullet.new(root, { sprite: circle });
    });

    pointSeries.data.setAll(window.jsonLocationData || []);

    // ========= LEGEND ROOT (SEPARATE DIV) =========
    const legendRoot = am5.Root.new("legenddiv");
    legendRoot.setThemes([am5themes_Animated.new(legendRoot)]);

    // hide amCharts logo only for the legend root
    legendRoot._logo?.dispose();

    legendRoot.container.setAll({
        width: am5.percent(100),
        height: am5.percent(100)
    });

    const legend = legendRoot.container.children.push(
        am5.Legend.new(legendRoot, {
            nameField: "name",
            width: am5.percent(100),
            height: am5.percent(100),
            layout: am5.GridLayout.new(legendRoot, {
                maxColumns: 4,
                fixedWidthGrid: true
            })
        })
    );

    legend.itemContainers.template.setAll({
        width: am5.percent(25),
        paddingTop: 10,
        paddingBottom: 0,
        paddingLeft: 2,
        paddingRight: 2,
        marginBottom: 8
    });

    legend.markers.template.setAll({ width: 10, height: 10, marginRight: 3 });

    legend.labels.template.setAll({
        fontSize: 10,
        fill: am5.color(0x000000),
        oversizedBehavior: "wrap",
        textAlign: "left",
        width: am5.percent(100)
    });

    legend.markerRectangles.template.adapters.add("fill", (fill, target) =>
        target.dataItem?.dataContext?.fill || fill
    );
    legend.markerRectangles.template.adapters.add("stroke", (stroke, target) =>
        target.dataItem?.dataContext?.fill || stroke
    );

    legend.data.setAll(
        (window.jsonLegendData || []).map((x) => ({
            name: x.title,
            fill: am5.color(x.color)
        }))
    );

    // ========= MAP DOWNLOAD (MAP ONLY) =========
    const exporting = am5plugins_exporting.Exporting.new(root, {
        filePrefix: "map"
    });

    async function downloadMapOnly(type) {
        await exporting.download(type); // "png" | "jpg"
    }

    // ========= PRINT (MAP + LEGEND) =========
    async function printMapWithLegend() {
        await new Promise(requestAnimationFrame);
        await new Promise(requestAnimationFrame);
        await new Promise((r) => setTimeout(r, 150));

        const wrap = document.getElementById("mapwrap");
        if (!wrap) return;

        const canvas = await html2canvas(wrap, {
            backgroundColor: "#ffffff",
            useCORS: true,
            allowTaint: true,
            scale: 2
        });

        const dataUrl = canvas.toDataURL("image/png");
        const w = window.open("", "_blank");
        w.document.write(`
      <html>
        <head>
          <title>Print</title>
          <style>
            @page { margin: 0; }
            body { margin: 0; }
            img { width: 100%; height: auto; display: block; }
          </style>
        </head>
        <body>
          <img id="img" src="${dataUrl}" />
          <script>
            const img = document.getElementById('img');
            img.onload = () => {
              setTimeout(() => {
                window.focus();
                window.print();
                window.onafterprint = () => window.close();
              }, 50);
            };
          </script>
        </body>
      </html>
    `);
        w.document.close();
    }

    // ========= DROPDOWN MENU (replaces buttons) =========
    (function makeDropdownMenu() {
        const wrap = document.getElementById("mapwrap");
        if (!wrap) return;

        wrap.style.position = wrap.style.position || "relative";

        const menuWrap = document.createElement("div");
        menuWrap.style.position = "absolute";
        menuWrap.style.top = "8px";
        menuWrap.style.right = "8px";
        menuWrap.style.zIndex = "9999";

        // Styling close to a "nice" dropdown
        menuWrap.innerHTML = `
      <style>
        .map-dd-btn {
          font-size: 12px;
          padding: 6px 10px;
          border: 1px solid #cfcfcf;
          background: #fff;
          border-radius: 4px;
          cursor: pointer;
        }
        .map-dd {
          position: relative;
          display: inline-block;
        }
        .map-dd-menu {
          display: none;
          position: absolute;
          right: 0;
          margin-top: 6px;
          min-width: 190px;
          background: #fff;
          border: 1px solid #cfcfcf;
          border-radius: 6px;
          box-shadow: 0 6px 18px rgba(0,0,0,0.12);
          padding: 6px;
        }
        .map-dd-menu button {
          width: 100%;
          text-align: left;
          font-size: 12px;
          padding: 8px 10px;
          border: 0;
          background: transparent;
          cursor: pointer;
          border-radius: 4px;
        }
        .map-dd-menu button:hover {
          background: #f2f2f2;
        }
        .map-dd.open .map-dd-menu {
          display: block;
        }
        .map-dd-sep {
          height: 1px;
          background: #e6e6e6;
          margin: 6px 0;
        }
      </style>

      <div class="map-dd" id="map-dd">
        <button type="button" class="map-dd-btn" id="map-dd-toggle">Download ▾</button>
        <div class="map-dd-menu" id="map-dd-menu">
          <button type="button" id="map-dd-png">PNG</button>
          <button type="button" id="map-dd-jpg">JPG</button>
          <div class="map-dd-sep"></div>
          <button type="button" id="map-dd-print">Print (map + legend)</button>
        </div>
      </div>
    `;

        wrap.appendChild(menuWrap);

        const dd = menuWrap.querySelector("#map-dd");
        const toggle = menuWrap.querySelector("#map-dd-toggle");
        const menu = menuWrap.querySelector("#map-dd-menu");

        function close() {
            dd.classList.remove("open");
        }
        function toggleOpen() {
            dd.classList.toggle("open");
        }

        toggle.addEventListener("click", (e) => {
            e.stopPropagation();
            toggleOpen();
        });

        // Close when clicking outside
        document.addEventListener("click", close);

        // Prevent inside clicks from closing unless we choose to
        menu.addEventListener("click", (e) => e.stopPropagation());

        menuWrap.querySelector("#map-dd-png").addEventListener("click", async () => {
            close();
            await downloadMapOnly("png");
        });

        menuWrap.querySelector("#map-dd-jpg").addEventListener("click", async () => {
            close();
            await downloadMapOnly("jpg");
        });

        menuWrap.querySelector("#map-dd-print").addEventListener("click", async () => {
            close();
            await printMapWithLegend();
        });
    })();
});