import { useEffect, useRef, useState } from 'react'
import {
  View,
  Text,
  Pressable,
  ScrollView,
  StyleSheet,
  Alert,
  Animated,
} from 'react-native'
import { FEATURE_ICON_MAP } from './FeatureIcons'
import ChildAvatar from './ChildAvatar'
import { childName } from '../utils/format'
import { ui } from './ui'

const GREY_TINT = '#94a3b8'
const GREY_SOFT = '#f1f5f9'
const COMING_SOON = 'Coming soon'

export default function FeatureDashboard({
  features = [],
  onSelectFeature,
  title = 'Dashboard',
  subtitle,
  child,
  userName,
}) {
  const [toast, setToast] = useState('')
  const toastOpacity = useRef(new Animated.Value(0)).current
  const toastTimer = useRef(null)

  useEffect(() => () => {
    if (toastTimer.current) clearTimeout(toastTimer.current)
  }, [])

  function notifyComingSoon() {
    Alert.alert(COMING_SOON, 'This feature is not available yet.', [{ text: 'OK' }])
    if (toastTimer.current) clearTimeout(toastTimer.current)
    setToast(COMING_SOON)
    toastOpacity.setValue(0)
    Animated.timing(toastOpacity, {
      toValue: 1,
      duration: 160,
      useNativeDriver: true,
    }).start()
    toastTimer.current = setTimeout(() => {
      Animated.timing(toastOpacity, {
        toValue: 0,
        duration: 200,
        useNativeDriver: true,
      }).start(({ finished }) => {
        if (finished) setToast('')
      })
    }, 2200)
  }

  function handlePress(feature) {
    if (feature.ready === false) {
      notifyComingSoon()
      return
    }
    onSelectFeature?.(feature.id)
  }

  return (
    <View style={styles.root}>
      <ScrollView contentContainerStyle={styles.scroll}>
        {child ? (
          <View style={styles.childHeader}>
            <ChildAvatar student={child} selected />
            <View style={styles.childHeaderText}>
              <Text style={styles.childHeaderName}>{childName(child)}</Text>
              <Text style={styles.childHeaderMeta}>
                {child?.school_class?.name || child?.section?.school_class?.name || child?.section?.schoolClass?.name || ''}
                {child?.section?.name ? ` · Section ${child.section.name}` : ''}
              </Text>
              {child?.admission_no ? (
                <Text style={styles.childHeaderSub}>Admission {child.admission_no}</Text>
              ) : null}
            </View>
          </View>
        ) : null}

        <View style={ui.heroCompact}>
          <Text style={ui.heroGreeting}>{title}</Text>
          <Text style={ui.heroName}>{userName || 'Welcome'}</Text>
          {subtitle ? <Text style={ui.heroMeta}>{subtitle}</Text> : null}
        </View>

        <View style={styles.grid}>
          {features.map((feature) => {
            const Icon = FEATURE_ICON_MAP[feature.id]
            const ready = feature.ready !== false
            const tint = ready ? feature.tint : GREY_TINT
            const soft = ready ? feature.soft : GREY_SOFT

            return (
              <Pressable
                key={feature.id}
                onPress={() => handlePress(feature)}
                style={({ pressed }) => [
                  styles.tile,
                  pressed && styles.tilePressed,
                ]}
                accessibilityRole="button"
                accessibilityLabel={ready ? feature.label : `${feature.label}, ${COMING_SOON}`}
              >
                <View style={[styles.iconWell, { backgroundColor: soft }]}>
                  {Icon ? <Icon size={28} color={tint} /> : null}
                </View>
                <Text style={[styles.tileLabel, !ready && styles.tileLabelMuted]} numberOfLines={2}>
                  {feature.label}
                </Text>
              </Pressable>
            )
          })}
        </View>
      </ScrollView>

      {toast ? (
        <Animated.View style={[styles.toast, { opacity: toastOpacity }]} pointerEvents="none">
          <Text style={styles.toastText}>{toast}</Text>
        </Animated.View>
      ) : null}
    </View>
  )
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  scroll: {
    padding: 16,
    paddingBottom: 32,
  },
  childHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 14,
    gap: 12,
  },
  childHeaderText: {
    flex: 1,
  },
  childHeaderName: {
    fontSize: 18,
    fontWeight: '700',
    color: '#0f172a',
  },
  childHeaderMeta: {
    fontSize: 13,
    color: '#64748b',
    marginTop: 2,
  },
  childHeaderSub: {
    fontSize: 12,
    color: '#94a3b8',
    marginTop: 2,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    marginHorizontal: -6,
  },
  tile: {
    width: '33.333%',
    paddingHorizontal: 6,
    paddingVertical: 10,
    alignItems: 'center',
  },
  tilePressed: {
    opacity: 0.75,
  },
  iconWell: {
    width: 64,
    height: 64,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  tileLabel: {
    fontSize: 12,
    fontWeight: '600',
    color: '#334155',
    textAlign: 'center',
    lineHeight: 16,
    minHeight: 32,
  },
  tileLabelMuted: {
    color: '#94a3b8',
  },
  toast: {
    position: 'absolute',
    left: 24,
    right: 24,
    top: 12,
    backgroundColor: 'rgba(15, 23, 42, 0.6)',
    borderRadius: 10,
    paddingVertical: 12,
    paddingHorizontal: 16,
    alignItems: 'center',
    zIndex: 20,
  },
  toastText: {
    color: '#fff',
    fontSize: 15,
    fontWeight: '600',
  },
})
