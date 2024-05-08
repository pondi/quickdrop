<template>

    <Head title="Welcome" />
    <GuestLayout>
        <Vueform
    validate-on="step|change"
  >
    <StaticElement
      name="h2"
      tag="h2"
      content="Drop your files"
      align="center"
    />
    <StaticElement
      name="divider"
      tag="hr"
    />
    <MultifileElement
      name="multifile"
      :drop="true"
      :upload-temp-endpoint="{
        url: '/store',
      }"
      :clickable="false"
      :remove-temp-endpoint="{
        url: '/deltemp',
      }"
      :remove-endpoint="{
        url: '/delete',
      }"
      accept=".tar,.bz,.gz,.zip,.7z,.bz2,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.pdf,.txt"
      :controls="{
        remove: false,
      }"
    />
  </Vueform>

</GuestLayout>
  </template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';

</script>
