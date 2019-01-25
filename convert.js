// http://srtm.csi.cgiar.org/download
// Usage: convert.js srtm_1.asc ...

const fs = require("fs");
const files = process.argv.slice(2);

files.forEach(file => {
  console.log(`Processing ${file}`);
  const out = convert(file);
  const filename = `strm3_${out.y}_${out.x}.strmb`;
  console.log(`Writting ${filename}`);
  fs.writeFileSync(filename, out.elevations);
});


function convert(filename) {
  const lines = fs.readFileSync(filename, {encoding: 'utf8'}).split("\n");
  const elevations = [];

  if (lines.length < 6000 + 6) {
    throw new Error(`Invalid file`);
  }

  let x;
  let y;

  // Process the header
  for (let i = 0; i < 6; i++) {
    let parts = lines.shift().split(/\s+/);
    if (parts.length >= 2) {
      switch (parts[0].toLowerCase()) {
        case "xllcorner":
          x = Math.round(parts[1]);
          break;
        case "yllcorner":
          y = Math.round(parts[1]);
          break;
      }
    }
  }

  if (x == null || y == null) {
    throw new Error(`Invalid file`);
  }

  // Process the elevation data
  for (let i = 0; i < 6000; i++) {
    let parts = lines.shift().split(/\s+/);
    if (parts.length < 6000) {
      throw new Error(`Invalid file`);
    }
    for (let j = 0; j < 6000; j++) {
      let elevation = Number(parts[j]);
      if (elevation < 0) {
        elevation = 0;
      }
      elevation = Math.round(1 + (elevation + 10) / 20);
      elevations.push(elevation);
    }
  }

  return {
    x: x,
    y: y,
    elevations: Uint8Array.from(elevations)
  };
}
