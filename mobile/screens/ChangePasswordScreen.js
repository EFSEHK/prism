import { useState } from 'react'
import { View, Text, TextInput, ScrollView, StyleSheet, ActivityIndicator, Pressable } from 'react-native'
import EyeIcon from '../components/EyeIcon'
import { ui } from '../components/ui'
import { validatePassword } from '../utils/passwordPolicy'

export default function ChangePasswordScreen({
  email,
  onEmailChange,
  currentPassword,
  onCurrentPasswordChange,
  newPassword,
  onNewPasswordChange,
  confirmPassword,
  onConfirmPasswordChange,
  loading,
  err,
  success,
  onSubmit,
  onBack,
  backLabel = 'Back to login',
}) {
  const [showCurrent, setShowCurrent] = useState(false)
  const [showNew, setShowNew] = useState(false)
  const [showConfirm, setShowConfirm] = useState(false)

  function handleSubmit() {
    if (newPassword !== confirmPassword) {
      onSubmit({ clientError: 'New passwords do not match.' })
      return
    }
    const policyError = validatePassword(newPassword, email)
    if (policyError) {
      onSubmit({ clientError: policyError })
      return
    }
    onSubmit()
  }

  return (
    <ScrollView style={styles.scroll} contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
      <Text style={styles.title}>Change password</Text>
      <Text style={styles.hint}>
        Password must include upper and lower case letters, a number, and a special character (min. 8 characters). It must not contain 3 consecutive characters from your username.
      </Text>

      <Text style={styles.label}>Admission no. / CNIC / Email</Text>
      <TextInput
        style={styles.input}
        value={email}
        onChangeText={onEmailChange}
        autoCapitalize="none"
        autoCorrect={false}
        editable={!loading}
      />

      <Text style={styles.label}>Current password</Text>
      <View style={styles.passwordField}>
        <TextInput
          style={styles.passwordInput}
          value={currentPassword}
          onChangeText={onCurrentPasswordChange}
          secureTextEntry={!showCurrent}
          editable={!loading}
        />
        <Pressable
          style={styles.passwordToggle}
          onPress={() => setShowCurrent((v) => !v)}
          disabled={loading}
          accessibilityLabel={showCurrent ? 'Hide password' : 'Show password'}
          hitSlop={8}
        >
          <EyeIcon hidden={showCurrent} />
        </Pressable>
      </View>

      <Text style={styles.label}>New password</Text>
      <View style={styles.passwordField}>
        <TextInput
          style={styles.passwordInput}
          value={newPassword}
          onChangeText={onNewPasswordChange}
          secureTextEntry={!showNew}
          editable={!loading}
        />
        <Pressable
          style={styles.passwordToggle}
          onPress={() => setShowNew((v) => !v)}
          disabled={loading}
          accessibilityLabel={showNew ? 'Hide password' : 'Show password'}
          hitSlop={8}
        >
          <EyeIcon hidden={showNew} />
        </Pressable>
      </View>

      <Text style={styles.label}>Confirm new password</Text>
      <View style={styles.passwordField}>
        <TextInput
          style={styles.passwordInput}
          value={confirmPassword}
          onChangeText={onConfirmPasswordChange}
          secureTextEntry={!showConfirm}
          editable={!loading}
          onSubmitEditing={handleSubmit}
        />
        <Pressable
          style={styles.passwordToggle}
          onPress={() => setShowConfirm((v) => !v)}
          disabled={loading}
          accessibilityLabel={showConfirm ? 'Hide password' : 'Show password'}
          hitSlop={8}
        >
          <EyeIcon hidden={showConfirm} />
        </Pressable>
      </View>

      {success ? <Text style={styles.ok}>{success}</Text> : null}
      {err ? <Text style={ui.err}>{err}</Text> : null}
      {loading ? <ActivityIndicator style={{ marginBottom: 12 }} /> : null}

      <Pressable style={[styles.button, loading && styles.buttonDisabled]} onPress={handleSubmit} disabled={loading}>
        <Text style={styles.buttonText}>{loading ? 'Please wait…' : 'Update password'}</Text>
      </Pressable>

      <Pressable style={styles.backLink} onPress={onBack} disabled={loading}>
        <Text style={styles.backLinkText}>{backLabel}</Text>
      </Pressable>
    </ScrollView>
  )
}

const styles = StyleSheet.create({
  scroll: { flex: 1 },
  container: { padding: 20, paddingTop: 48, paddingBottom: 40, flexGrow: 1 },
  title: { fontSize: 24, fontWeight: '700', marginBottom: 8 },
  hint: { fontSize: 12, color: '#64748b', marginBottom: 16, lineHeight: 18 },
  label: { fontSize: 14, color: '#334155', marginBottom: 4 },
  input: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    padding: 12,
    marginBottom: 12,
    backgroundColor: '#fff',
  },
  passwordField: {
    position: 'relative',
    marginBottom: 12,
  },
  passwordInput: {
    borderWidth: 1,
    borderColor: '#cbd5e1',
    borderRadius: 8,
    padding: 12,
    paddingRight: 48,
    backgroundColor: '#fff',
  },
  passwordToggle: {
    position: 'absolute',
    right: 4,
    top: 0,
    bottom: 0,
    width: 44,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ok: { color: '#15803d', marginBottom: 8 },
  button: {
    backgroundColor: '#2563eb',
    paddingVertical: 14,
    borderRadius: 8,
    alignItems: 'center',
  },
  buttonDisabled: { opacity: 0.6 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
  backLink: {
    marginTop: 16,
    paddingVertical: 12,
    alignItems: 'center',
  },
  backLinkText: { color: '#2563eb', fontWeight: '600' },
})
