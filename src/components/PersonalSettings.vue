<template>
	<div id="outline_prefs" class="section">
		<h2>
			<OutlineIcon class="icon" />
			{{ t('outline', 'Outline Integration') }}
		</h2>
		<div id="outline-content">
			<p class="settings-hint">
				<InformationOutlineIcon :size="24" class="icon" />
				{{ t('outline', 'Enter your Outline API key below to enable integration.') }}
			</p>
			<div class="line">
				<label for="outline-url">
					<EarthIcon :size="20" class="icon" />
					{{ t('outline', 'Outline instance address') }}
				</label>
				<input id="outline-url"
					v-model="state.url"
					type="text"
					:placeholder="t('outline', 'Outline instance address')"
					@input="onInput">
			</div>
			<div class="line">
				<label for="outline-key">
					<KeyIcon :size="20" class="icon" />
					{{ t('outline', 'API Key') }}
				</label>
				<input id="outline-key"
					v-model="state.api_key"
					type="password"
					:placeholder="t('outline', 'Your Outline API key')"
					@input="onSensitiveInput">
			</div>
		</div>
	</div>
</template>

<script>
import KeyIcon from 'vue-material-design-icons/Key.vue'
import EarthIcon from 'vue-material-design-icons/Earth.vue'
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'

import OutlineIcon from './icons/OutlineIcon.vue'

import axios from '@nextcloud/axios'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { delay } from '../utils.js'

export default {
	name: 'PersonalSettings',

	components: {
		KeyIcon,
		InformationOutlineIcon,
		EarthIcon,
		OutlineIcon,
	},

	data() {
		return {
			state: loadState('outline', 'user-config'),
			loading: false,
		}
	},

	methods: {
		onInput() {
			this.loading = true
			delay(() => {
				this.saveOptions({
					url: this.state.url,
				})
			}, 2000)()
		},
		onSensitiveInput() {
			this.loading = true
			delay(async () => {
				if (this.state.api_key === 'dummyKey') {
					return
				}

				await confirmPassword()

				this.saveOptions({
					api_key: this.state.api_key,
				})
			}, 2000)()
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl(`/apps/outline/${values.api_key !== undefined ? 'sensitive-' : ''}config`)
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('outline', 'Outline options saved'))
				})
				.catch((error) => {
					showError(
						t('outline', 'Failed to save Outline options')
						+ ': ' + (error.response?.request?.responseText ?? ''),
					)
					console.error(error)
				})
				.then(() => {
					this.loading = false
				})
		},
	},
}
</script>

<style scoped lang="scss">
#outline_prefs {
	#outline-content {
		margin-left: 40px;
	}

	h2 .icon {
		margin-right: 8px;
	}

	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		.icon {
			margin-right: 4px;
		}
	}

	.line {
		> label {
			width: 250px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 350px;
		}
	}
}
</style>
