import { View, Text, Pressable, StyleSheet } from 'react-native'
import { childName } from '../utils/format'

function initials(student) {
  const first = student?.first_name?.[0] ?? ''
  const last = student?.last_name?.[0] ?? ''
  return (first + last).toUpperCase() || '?'
}

const AVATAR_COLORS = ['#2563eb', '#7c3aed', '#db2777', '#059669', '#d97706']

function colorForId(id) {
  return AVATAR_COLORS[(Number(id) || 0) % AVATAR_COLORS.length]
}

export default function ChildAvatar({ student, selected, onPress }) {
  const name = childName(student)
  const content = (
    <View style={styles.wrap}>
      <View
        style={[
          styles.circle,
          { backgroundColor: colorForId(student?.id) },
          selected && styles.circleSelected,
        ]}
      >
        <Text style={styles.initials}>{initials(student)}</Text>
      </View>
      <Text style={[styles.name, selected && styles.nameSelected]} numberOfLines={2}>
        {name}
      </Text>
    </View>
  )

  if (onPress) {
    return (
      <Pressable onPress={onPress} style={({ pressed }) => [styles.pressable, pressed && { opacity: 0.85 }]}>
        {content}
      </Pressable>
    )
  }

  return <View style={styles.pressable}>{content}</View>
}

const styles = StyleSheet.create({
  pressable: {
    width: 96,
    alignItems: 'center',
    marginRight: 12,
  },
  wrap: {
    alignItems: 'center',
  },
  circle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    alignItems: 'center',
    justifyContent: 'center',
    borderWidth: 3,
    borderColor: 'transparent',
  },
  circleSelected: {
    borderColor: '#1e40af',
  },
  initials: {
    color: '#fff',
    fontSize: 22,
    fontWeight: '700',
  },
  name: {
    marginTop: 8,
    fontSize: 13,
    fontWeight: '600',
    color: '#334155',
    textAlign: 'center',
  },
  nameSelected: {
    color: '#1e40af',
  },
})
