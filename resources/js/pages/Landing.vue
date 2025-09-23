<script setup lang="ts">
import AppHeader from '@/components/AppHeader.vue'
import AppLogo from '@/components/AppLogo.vue'
import { Head, Link } from '@inertiajs/vue3'
import { dashboard, login, register } from '@/routes'
</script>

<template>
  <Head title="DoBoard – Organisez, collaborez, créez">
    <link rel="preconnect" href="https://rsms.me/" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    <meta
      name="description"
      content="DoBoard est un espace de travail en ligne qui combine tableau blanc, gestion de tâches et stockage cloud. Organisez, collaborez et partagez au même endroit."
    />
  </Head>

  <div class="min-h-screen bg-background text-foreground">
    <!-- Header -->
    <AppHeader />

    <!-- Hero -->
    <section class="relative isolate overflow-hidden">
      <div class="mx-auto w-full max-w-7xl px-6 py-16 md:py-24">
        <div class="grid items-center gap-10 md:grid-cols-2">
          <div>
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
              <span class="badge badge-info">Nouveau</span>
              <span>Collaborez en temps réel</span>
            </div>
            <h1 class="mt-4 text-4xl font-semibold tracking-tight md:text-5xl lg:text-6xl">
              Gardez vos idées, tâches et fichiers au même endroit
            </h1>
            <p class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground md:text-lg">
              DoBoard combine tableau blanc, gestion de tâches et stockage cloud dans une interface claire et moderne.
              Idéal en solo comme en équipe, pour travailler avec fluidité et efficacité.
            </p>
            <div class="mt-6 flex flex-wrap items-center gap-3">
              <Link :href="register()" class="btn btn-primary">
                Commencer gratuitement
              </Link>
              <Link :href="login()" class="btn btn-outline">
                Se connecter
              </Link>
              <Link v-if="$page.props.auth?.user" :href="dashboard()" class="btn btn-ghost">
                Aller au dashboard
              </Link>
            </div>
            <div class="mt-3 text-xs text-muted-foreground">
              Pas de carte requise • Essayez en quelques secondes
            </div>
          </div>

          <!-- Mockup card -->
          <div class="relative">
            <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
              <div class="flex items-center justify-between border-b px-4 py-2">
                <div class="flex items-center gap-2">
                  <AppLogo />
                  <span class="text-sm font-medium">DoBoard</span>
                </div>
                <div class="flex gap-1">
                  <span class="h-2 w-2 rounded-full bg-red-500/80" />
                  <span class="h-2 w-2 rounded-full bg-yellow-500/80" />
                  <span class="h-2 w-2 rounded-full bg-green-500/80" />
                </div>
              </div>
              <div class="grid gap-3 p-4 md:grid-cols-2">
                <div class="rounded-md border p-3">
                  <p class="text-xs font-medium text-muted-foreground">Tâches</p>
                  <ul class="mt-2 space-y-2 text-sm">
                    <li class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-primary/70" />Planifier la release</li>
                    <li class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-primary/70" />Préparer la démo</li>
                    <li class="flex items-center gap-2"><span class="size-1.5 rounded-full bg-primary/70" />Relire les specs</li>
                  </ul>
                </div>
                <div class="rounded-md border p-3">
                  <p class="text-xs font-medium text-muted-foreground">Tableau blanc</p>
                  <div class="mt-2 h-28 rounded-sm bg-muted/40"></div>
                </div>
                <div class="rounded-md border p-3 md:col-span-2">
                  <p class="text-xs font-medium text-muted-foreground">Fichiers</p>
                  <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                    <div class="rounded-md border p-2">Brief.pdf</div>
                    <div class="rounded-md border p-2">UX.sketch</div>
                    <div class="rounded-md border p-2">notes.md</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section class="border-t">
      <div class="mx-auto max-w-7xl px-6 py-16 md:py-20">
        <div class="grid gap-6 md:grid-cols-3">
          <div class="rounded-lg border p-6">
            <h3 class="text-lg font-semibold">Gestion de tâches</h3>
            <p class="mt-2 text-sm text-muted-foreground">Créez des tâches, checklists et dossiers. Classez, priorisez et suivez l’avancement facilement.</p>
          </div>
          <div class="rounded-lg border p-6">
            <h3 class="text-lg font-semibold">Tableau blanc</h3>
            <p class="mt-2 text-sm text-muted-foreground">Visualisez vos idées, dessinez des flux et collaborez en temps réel sur un canvas flexible.</p>
          </div>
          <div class="rounded-lg border p-6">
            <h3 class="text-lg font-semibold">Stockage cloud</h3>
            <p class="mt-2 text-sm text-muted-foreground">Importez et partagez des fichiers. Retrouvez documents et médias au même endroit.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Collaboration -->
    <section class="border-t">
      <div class="mx-auto max-w-7xl px-6 py-16 md:py-20">
        <div class="grid items-center gap-10 md:grid-cols-2">
          <div>
            <h2 class="text-2xl font-semibold md:text-3xl">Collaborez en temps réel</h2>
            <p class="mt-3 text-sm text-muted-foreground md:text-base">
              Invitez votre équipe, commentez, mentionnez et partagez des espaces. DoBoard simplifie la coordination
              des projets, du brainstorming à la livraison.
            </p>
            <div class="mt-6">
              <Link :href="register()" class="btn btn-primary">
                Créer mon espace
              </Link>
            </div>
          </div>
          <div class="rounded-xl border bg-card p-4 text-card-foreground shadow-sm">
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
              <span class="size-2 rounded-full bg-green-500/80" />
              Synchro instantanée • Historique des modifications • Partage sécurisé
            </div>
            <div class="mt-3 h-40 rounded-md bg-muted/40"></div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="border-t">
      <div class="mx-auto max-w-7xl px-6 py-16 md:py-20">
        <div class="rounded-2xl border bg-gradient-to-br from-primary/10 to-transparent p-10 text-center">
          <h2 class="text-2xl font-semibold md:text-3xl">Prêt à travailler avec fluidité ?</h2>
          <p class="mx-auto mt-2 max-w-2xl text-sm text-muted-foreground md:text-base">
            Rejoignez DoBoard et regroupez idées, documents et projets sur une seule plateforme.
          </p>
          <div class="mt-6 flex flex-wrap justify-center gap-3">
            <Link :href="register()" class="btn btn-primary">
              S'inscrire
            </Link>
            <Link :href="login()" class="btn btn-outline">
              Se connecter
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t">
      <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-8 md:flex-row">
        <div class="flex items-center gap-2 text-sm text-muted-foreground">
          <AppLogo />
          <span>© {{ new Date().getFullYear() }} DoBoard</span>
        </div>
        <div class="text-xs text-muted-foreground">
          Fabriqué avec Laravel, InertiaJS et Tailwind CSS
        </div>
      </div>
    </footer>
  </div>
</template>
