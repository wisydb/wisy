let fireworks = [];
let gravity;
let fireworkStart;

function setup() {
  createCanvas(innerWidth, innerHeight);

  gravity = createVector(0, 0.14);
}

function draw() {
  background(255, 255, 255);
  if (!fireworkStart) {
    fireworkStart = Date.now();
  }

  if (random(1) < 0.05) {
    fireworks.push(new Firework());
  }

  for (let i = fireworks.length - 1; i >= 0; i--) {
    fireworks[i].update();
    fireworks[i].show();

    if (fireworks[i].done()) {
      console.log('firwork spliced')
      fireworks.splice(i, 1);
    }
  }
}
