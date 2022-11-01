// Daniel Shiffman
// http://codingtra.in
// http://patreon.com/codingtrain
// Code for: https://youtu.be/CKeyIbT3vXI
class Firework {
    constructor() {
        this.hu = [random(220, 255), random(125, 225), random(40, 130)];
        this.firework = new Particle(random(width), height, this.hu, true);
        this.exploded = false;
        this.particles = [];
        this.done = function () {
            if (this.exploded && this.particles.length === 0) {
                return true;
            }
            else {
                return false;
            }
        };
        this.update = function () {
            if (!this.exploded) {
                this.firework.applyForce(gravity);
                this.firework.update();
                if (this.firework.vel.y >= 0) {
                    this.exploded = true;
                    this.explode();
                }
            }
            for (var i = this.particles.length - 1; i >= 0; i--) {
                this.particles[i].applyForce(gravity);
                this.particles[i].update();
                if (this.particles[i].done()) {
                    this.particles.splice(i, 1);
                }
            }
            // console.log(this.particles.length)
        };
        this.explode = function () {
            for (var i = 0; i < 70; i++) {
                var p = new Particle(this.firework.pos.x, this.firework.pos.y, this.hu, false);
                this.particles.push(p);
            }
        };
        this.show = function () {
            if (!this.exploded) {
                var p = new TailParticle(this.firework.pos.x, this.firework.pos.y, this.hu, false);
                this.particles.push(p);
                this.firework.show();
            }
            for (var i = 0; i < this.particles.length; i++) {
                this.particles[i].show();
            }
        };
    }
}
