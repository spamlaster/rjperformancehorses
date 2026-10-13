<template>
  <PublicLayout>
    <section class="home-hero">
      <div class="hero-copy">
        <h1>RJ</h1>
        <p class="hero-brand">Performance Horses</p>
        <p class="hero-script">Confidence in the Saddle</p>
        <p class="hero-services">Riding Lessons <span>•</span> Barrel &amp; Pole Training <span>•</span> Horsemanship</p>
        <div class="hero-actions">
          <router-link to="/book" class="ranch-button">Book a Lesson</router-link>
          <router-link to="/lessons" class="ranch-button ranch-button-outline">Learn More</router-link>
        </div>
      </div>
    </section>

    <section class="home-about">
      <img src="/images/Golden Hour Horse Bond.png" alt="Rylie sharing a moment with her chestnut horse" />
      <div class="about-copy">
        <h2>About Rylie</h2>
        <p>Hi, I'm Rylie! I'm passionate about helping riders of all levels build confidence, learn solid horsemanship, and have fun in the process. Whether you're just getting started or looking to improve your barrel and pole skills, my goal is to create a positive, supportive, and encouraging environment for every rider.</p>
        <router-link to="/about" class="ranch-button">Meet Rylie</router-link>
      </div>
    </section>

    <section class="home-lessons">
      <h2><span>Lessons &amp; Training</span></h2>
      <div class="lesson-grid">
        <article v-for="lesson in lessons" :key="lesson.title" class="home-lesson">
          <img :src="lesson.image" :alt="lesson.alt" />
          <h3>{{ lesson.title }}</h3>
          <p>{{ lesson.description }}</p>
          <router-link to="/lessons" class="ranch-button lesson-button">View Options</router-link>
        </article>
      </div>
    </section>

    <section class="home-ready">
      <h2>Ready to Ride?</h2>
      <p>Check out my current availability and book your lesson online.</p>
      <router-link to="/book" class="ranch-button">Book a Lesson</router-link>
    </section>
  </PublicLayout>
</template>

<script setup lang="ts">
import PublicLayout from '@/components/layouts/PublicLayout.vue'
const lessons = [
  { title: 'Private Riding Lessons', image: '/images/Sunset Cowgirl in the Arena.png', alt: 'Cowgirl riding a chestnut horse in the arena', description: 'One-on-one instruction tailored to your goals and experience level.' },
  { title: 'Joined / Sibling Lessons', image: '/images/Golden Hour Riders in the Arena.png', alt: 'Two riders learning together in the arena', description: 'Perfect for siblings or friends who want to ride together.' },
  { title: 'Barrel & Pole Lessons', image: '/images/Barrel Racing at Full Speed.png', alt: 'Rider rounding a blue barrel', description: 'For more experienced riders looking to improve their skills.' },
]
</script>

<style scoped>
.home-hero { position: relative; width: 100%; height: 35.57vw; min-height: 340px; display: flex; align-items: center; background: url('/images/Sunset Cowgirl Over the Mountain Arena.png') center center / cover no-repeat; color: white; }
.home-hero::before { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, rgb(0 0 0 / 28%), rgb(0 0 0 / 14%) 55%, transparent 75%); }
.hero-copy { position: relative; width: 65%; text-align: center; padding: 20px 3%; text-shadow: 0 2px 5px #0008; }
.hero-copy h1 { font-size: clamp(52px, 6.8vw, 88px); font-weight: 400; margin: 0; line-height: 1; }
.hero-brand { text-transform: uppercase; letter-spacing: .16em; font-size: clamp(18px, 2.6vw, 34px); margin: 12px 0 8px; }
.hero-script { font-family: var(--font-script); font-size: clamp(32px, 4.5vw, 58px); line-height: 1.15; margin: 0 0 14px; }
.hero-services { font-size: clamp(10px, 1.3vw, 17px); text-transform: uppercase; letter-spacing: .07em; margin-bottom: 28px; }
.hero-services span { padding: 0 8px; }
.hero-actions { display: flex; justify-content: center; gap: 18px; }
.ranch-button { display: inline-block; border: 1px solid #694226; border-radius: 999px; background: linear-gradient(110deg, #89603b, #754c2e); color: #fff; padding: 10px 36px; font-size: 16px; line-height: 1.25; text-shadow: none; transition: background .2s; }
.ranch-button:hover { background: #603b25; color: #fff; }
.ranch-button-outline { border-color: white; background: transparent; min-width: 190px; }
.home-about { position: relative; margin-top: 16px; min-height: 365px; overflow: hidden; background: #fff; display: flex; align-items: center; }
.home-about img { position: absolute; right: 0; top: 0; width: 55%; height: 100%; object-fit: cover; object-position: center; }
.home-about::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, #fff 42%, rgb(255 255 255 / 96%) 47%, rgb(255 255 255 / 65%) 53%, rgb(255 255 255 / 18%) 60%, transparent 68%); }
.about-copy { position: relative; z-index: 1; width: 49%; padding: 28px 0 26px 7.5%; }
.about-copy h2 { font-family: 'Allura', var(--font-script); font-size: clamp(46px, 5vw, 68px); color: #482b1c; font-weight: 400; margin: 0 0 12px; }
.about-copy p { font-family: 'Times New Roman', Georgia, serif; font-weight: 400; color: #342c26; font-size: clamp(16px, 1.7vw, 21px); line-height: 1.28; margin-bottom: 20px; }
.about-copy .ranch-button { padding: 8px 34px; }
.home-lessons { padding: 26px 3.5% 25px; background: #fcf9f3; }
.home-lessons > h2 { display: flex; align-items: center; gap: 15px; justify-content: center; text-transform: uppercase; letter-spacing: .15em; font-size: clamp(20px, 2.4vw, 30px); font-weight: 400; margin: 0 10% 22px; white-space: nowrap; }
.home-lessons > h2::before, .home-lessons > h2::after { content: ''; height: 1px; background: #b68a67; flex: 1; }
.lesson-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.home-lesson { text-align: center; }
.home-lesson img { width: 100%; aspect-ratio: 1.35; object-fit: cover; border-radius: 4px; }
.home-lesson h3 { font-size: clamp(18px, 2vw, 25px); font-weight: 400; margin: 9px 0 12px; position: relative; }
.home-lesson h3::after { content: ''; position: absolute; bottom: -6px; left: 44%; width: 12%; height: 1px; background: #b68a67; }
.home-lesson p { font-size: clamp(15px, 1.5vw, 19px); line-height: 1.3; max-width: 270px; margin: 0 auto 14px; }
.lesson-button { background: transparent; color: #482b1c; padding: 6px 36px; }
.home-ready { padding: 60px 24px 44px; text-align: center; color: white; background: linear-gradient(#0006, #0006), url('/images/Golden Fence at Mountain Sunset.png') center 52% / cover; }
.home-ready h2 { text-transform: uppercase; letter-spacing: .15em; font-weight: 400; font-size: 30px; margin-bottom: 12px; }
.home-ready p { font-size: 19px; margin-bottom: 22px; }
.home-ready .ranch-button { padding: 14px 55px; }
@media (max-width: 767px) {
  .home-hero { aspect-ratio: auto; height: 430px; align-items: flex-end; background-size: auto 100%; background-position: 72% top; }
  .home-hero::before { background: linear-gradient(transparent 30%, #0005 55%, #000e); }
  .hero-copy { width: 100%; padding: 0 24px 18px; text-align: left; }
  .hero-copy h1, .hero-brand { display: none; }
  .hero-script { font-size: 47px; max-width: 270px; line-height: 1.03; margin-bottom: 14px; }
  .hero-services { font-size: 11px; line-height: 1.5; letter-spacing: .12em; margin-bottom: 15px; }
  .hero-services span { padding: 0 4px; }
  .hero-actions .ranch-button { width: 100%; text-align: center; }
  .hero-actions .ranch-button-outline { display: none; }
  .home-about { min-height: 340px; margin-top: 0; }
  .about-copy { width: 65%; padding: 24px 16px; }
  .about-copy h2 { font-size: 40px; }
  .about-copy p { font-family: 'Times New Roman', Georgia, serif; font-weight: 400; color: #342c26; font-size: 15px; }
  .home-about img { width: 55%; object-position: 60%; }
  .home-about::after { background: linear-gradient(90deg, #fff 45%, rgb(255 255 255 / 87%) 57%, transparent 85%); }
  .home-lessons { padding: 28px 20px; }
  .home-lessons > h2 { margin: 0 0 20px; font-size: 20px; gap: 8px; }
  .lesson-grid { grid-template-columns: 1fr; gap: 30px; }
  .home-lesson h3 { font-size: 23px; }
  .home-lesson p { font-size: 17px; }
  .home-ready { padding: 42px 20px; }
}
</style>
