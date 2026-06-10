<script setup>
  import { ref, watch } from 'vue';
  import { useForm, router, Link } from '@inertiajs/vue3';
  import debounce from 'lodash/debounce';

  const props = defineProps({
    tasks: Object,
    filters: Object,
  });

  // useForm exposes processing, errors, isDirty, and resetting out of the box
  const form = useForm({
    title: '',
  });

  const search = ref(props.filters.search ?? '');
  const status = ref(props.filters.status ?? 'all');

  const submit = () => {
    form.post(route('tasks.store'), {
      onSuccess: () => form.reset('title'),
      onError: () => {
        console.log('Validation execution halted due to faulty data inputs.');
      },
    });
  };

  const toggleTask = (task) => {
    router.put(route('tasks.update', task.id), {}, { preserveScroll: true });
  };

  const deleteTask = (id) => {
    if (confirm('Are you sure?')) {
      router.delete(route('tasks.destroy', id), { preserveScroll: true });
    }
  };

  const filterData = () => {
    router.get(
      route('tasks.index'),
      { search: search.value, status: status.value },
      { preserveState: true, preserveScroll: true, replace: true }
    );
  };

  watch(status, () => filterData());
  watch(
    search,
    debounce(() => filterData(), 300)
  );
</script>

<template>
  <div class="max-w-xl mx-auto mt-12 p-6 bg-slate-900 text-white rounded-xl shadow-xl">
    <h2 class="text-xl font-bold mb-6">SQLite Project Hub</h2>

    <div class="flex gap-3 mb-6 bg-slate-800/50 p-3 rounded-lg border border-slate-800">
      <div class="flex-1">
        <input
          v-model="search"
          type="text"
          placeholder="Search tasks..."
          class="w-full bg-slate-900 border border-slate-700 rounded p-2 text-sm text-white focus:outline-none focus:border-indigo-500"
        />
      </div>
      <div>
        <select
          v-model="status"
          class="bg-slate-900 border border-slate-700 rounded p-2 text-sm text-white focus:outline-none focus:border-indigo-500"
        >
          <option value="all">All Statuses</option>
          <option value="pending">Pending Only</option>
          <option value="completed">Completed Only</option>
        </select>
      </div>
    </div>

    <form @submit.prevent="submit" class="mb-6">
      <div class="flex gap-2">
        <input
          v-model="form.title"
          type="text"
          placeholder="Enter new element name..."
          class="flex-1 bg-slate-800 border rounded p-2 text-white focus:outline-none transition-all duration-200"
          :class="{
            'border-slate-700 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500':
              !form.errors.title,
            'border-red-500 bg-red-950/20 text-red-200 focus:border-red-500 focus:ring-1 focus:ring-red-500':
              form.errors.title,
          }"
          @input="form.clearErrors('title')"
        />
        <button
          type="submit"
          :disabled="form.processing || !form.isDirty"
          class="bg-indigo-600 hover:bg-indigo-500 px-4 py-2 rounded text-sm font-semibold transition-all duration-200 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          <span v-if="form.processing">Saving...</span>
          <span v-else>Submit</span>
        </button>
      </div>

      <div class="h-5 mt-1 overflow-hidden transition-all duration-300">
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="transform -translate-y-2 opacity-0"
          enter-to-class="transform translate-y-0 opacity-100"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="transform translate-y-0 opacity-100"
          leave-to-class="transform -translate-y-2 opacity-0"
        >
          <p
            v-if="form.errors.title"
            class="text-red-400 text-xs font-medium flex items-center gap-1"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-3.5 w-3.5"
              viewBox="0 0 20 20"
              fill="currentColor"
            >
              <path
                fill-rule="evenodd"
                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                clip-rule="evenodd"
              />
            </svg>
            {{ form.errors.title }}
          </p>
        </Transition>
      </div>
    </form>

    <div
      v-if="form.wasSuccessful"
      class="mb-4 bg-emerald-950/30 border border-emerald-500/40 text-emerald-400 p-2.5 rounded text-xs flex items-center gap-2"
    >
      ✓ Entry verified and committed to SQLite storage.
    </div>

    <div class="border-t border-slate-800 pt-4">
      <h3 class="text-sm font-semibold tracking-wider text-slate-400 uppercase mb-3">
        Stored DB Records (Page {{ tasks.current_page }} of {{ tasks.last_page }})
      </h3>

      <ul v-if="tasks.data.length > 0" class="space-y-2">
        <li
          v-for="task in tasks.data"
          :key="task.id"
          class="bg-slate-800 p-3 rounded border border-slate-700/50 flex justify-between items-center transition"
          :class="{ 'opacity-50 line-through bg-slate-850': task.is_completed }"
        >
          <div class="flex items-center gap-3">
            <input
              type="checkbox"
              :checked="task.is_completed"
              @change="toggleTask(task)"
              class="rounded text-indigo-600 bg-slate-900 border-slate-700 focus:ring-indigo-500 h-4 w-4"
            />
            <span class="text-sm">{{ task.title }}</span>
          </div>

          <div class="flex items-center gap-2">
            <span
              class="text-xs text-indigo-400 bg-indigo-950 px-2 py-0.5 rounded border border-indigo-900"
              >ID: {{ task.id }}</span
            >
            <button
              @click="deleteTask(task.id)"
              class="text-slate-400 hover:text-red-400 p-1 rounded transition"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                />
              </svg>
            </button>
          </div>
        </li>
      </ul>

      <p v-else class="text-slate-500 text-sm italic">
        No records found matching current criteria.
      </p>

      <div v-if="tasks.links.length > 3" class="mt-6 flex flex-wrap justify-center gap-1">
        <Component
          :is="link.url ? Link : 'span'"
          v-for="(link, index) in tasks.links"
          :key="index"
          :href="link.url"
          v-html="link.label"
          class="px-3 py-1.5 text-xs rounded border transition"
          :class="{
            'bg-slate-800 text-slate-400 border-slate-700 cursor-not-allowed': !link.url,
            'bg-indigo-600 text-white border-indigo-600 font-bold': link.active,
            'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800':
              link.url && !link.active,
          }"
          preserve-scroll
        />
      </div>
    </div>
  </div>
</template>
