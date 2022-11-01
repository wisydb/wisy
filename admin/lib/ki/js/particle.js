// Daniel Shiffman
// http://codingtra.in
// http://patreon.com/codingtrain
// Code for: https://youtu.be/CKeyIbT3vXI

class Particle {
    constructor(x, y, hu, firework) {
        this.pos = createVector(x, y);
        this.firework = firework;
        this.lifespan = 255;
        this.hu = hu;
        this.acc = createVector(0, 0);
        if (this.firework) {
            this.vel = createVector(random(-2,2), random(-16, -8));
        }
        else {
            this.vel = p5.Vector.random2D();
            this.vel.mult(random(2, 10));
        }
        this.applyForce = function (force) {
            this.acc.add(force);
        };
        this.update = function () {
            if (!this.firework) {
                this.vel.mult(0.9);
                this.lifespan -= 4;
            }
            this.vel.add(this.acc);
            this.pos.add(this.vel);
            this.acc.mult(0);
        };
        this.done = function () {
            if (this.lifespan < 0) {
                return true;
            }
            else {
                return false;
            }
        };
        this.show = function () {
            // colorMode(HSB);
            if (!this.firework) {
                strokeWeight(4);
                stroke(hu[0], hu[1], hu[2], this.lifespan);
            }
            else {
                strokeWeight(4);
                stroke(hu[0], hu[1], hu[2]);
            }
            point(this.pos.x, this.pos.y);
            // colorMode(RGB);
        };
    }
}
