class TailParticle extends Particle {
    constructor(x, y, hu, firework) {
        super(x,  y, hu, firework);
        this.lifespan = 170;
      
        if (this.firework) {
            this.vel = createVector(random(-1,1), random(-16, -8));
        }
        else {
            this.vel = p5.Vector.random2D();
            this.vel.mult(random(0, 1));
        }
    }
}